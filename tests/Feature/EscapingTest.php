<?php

use App\Mail\PortfolioCreatedMail;
use App\Mail\PortfolioDeletedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Texte saisi par l'utilisateur qui exécuterait un script s'il sortait tel quel dans le HTML.
 */
function dangerousText(): string
{
    return '<script>alert("xss")</script>';
}

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();

    $this->portfolio = $this->owner->portfolios()->create([
        'name' => dangerousText(),
        'description' => dangerousText(),
    ]);
    $this->account = $this->portfolio->accounts()->create([
        'name' => dangerousText(),
        'description' => dangerousText(),
    ]);
    $this->analytic_axis = $this->portfolio->analyticAxes()->create([
        'name' => dangerousText(),
        'description' => dangerousText(),
    ]);
    $this->operation = $this->account->operations()->create([
        'label' => dangerousText(),
        'amount' => '10.00',
        'delivery_at' => '2026-01-15',
    ]);
    $this->operation->analyticLines()->create([
        'analytic_axis_id' => $this->analytic_axis->id,
        'percentage' => '100.00',
    ]);
});

test('pages escape the names and descriptions typed by the user', function (string $route, string $model, array $query) {
    $response = $this->actingAs($this->owner)->get(route($route, [$this->{$model}, ...$query]));

    $response
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(', escape: false)
        ->assertDontSee(dangerousText(), escape: false);
})->with([
    ['portfolio.show', 'portfolio', []],
    ['portfolio.edit', 'portfolio', []],
    ['account.show', 'account', ['page' => 1]],
    ['account.edit', 'account', []],
    ['operation.create', 'account', []],
    ['operation.edit', 'operation', []],
    ['analytic-axis.index', 'portfolio', []],
    ['analytic-axis.edit', 'analytic_axis', []],
]);

test('the portfolio list and its flash message escape the name of a new portfolio', function () {
    $response = $this->actingAs($this->owner)->followingRedirects()->post(route('portfolio.store'), [
        'name' => dangerousText(),
        'description' => '',
    ]);

    $response
        ->assertOk()
        ->assertSee('« &lt;script&gt;alert(', escape: false)
        ->assertDontSee(dangerousText(), escape: false);
});

test('flash messages escape the name they quote', function (string $method, string $route, string $model, array $data) {
    $response = $this->actingAs($this->owner)->followingRedirects()->{$method}(route($route, $this->{$model}), $data);

    $response
        ->assertOk()
        ->assertSee('« &lt;script&gt;alert(', escape: false)
        ->assertDontSee(dangerousText(), escape: false);
})->with([
    'portfolio update' => ['put', 'portfolio.update', 'portfolio', ['name' => dangerousText(), 'description' => '']],
    'account archiving' => ['delete', 'account.destroy', 'account', []],
    'analytic axis archiving' => ['delete', 'analytic-axis.destroy', 'analytic_axis', []],
]);

test('the account search escapes the search it repeats', function () {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [
        $this->portfolio,
        'search' => '<b onmouseover=alert(1)>introuvable',
    ]));

    $response
        ->assertOk()
        ->assertSee('« &lt;b onmouseover=alert(1)&gt;introuvable »', escape: false)
        ->assertDontSee('<b onmouseover', escape: false);
});

test('the portfolio mails escape the portfolio name', function (string $mailable) {
    $this->portfolio->delete();

    $content = (new $mailable($this->portfolio))->render();

    expect($content)
        ->toContain('&lt;script&gt;')
        ->not->toContain(dangerousText());
})->with([
    PortfolioCreatedMail::class,
    PortfolioDeletedMail::class,
]);
