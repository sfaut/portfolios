<?php

use App\Mail\PortfolioCreatedMail;
use App\Mail\PortfolioDeletedMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
    ['portfolio.dashboard', 'portfolio', []],
    ['portfolio.show', 'portfolio', []],
    ['portfolio.edit', 'portfolio', []],
    ['account.show', 'account', ['page' => 1]],
    ['account.edit', 'account', []],
    ['operation.create', 'account', []],
    ['operation.edit', 'operation', []],
    ['ledger.index', 'portfolio', []],
    ['analytic-axis.index', 'portfolio', []],
    ['analytic-axis.edit', 'analytic_axis', []],
]);

test('the dashboard escapes the analytic axis names of the sales', function () {
    $this->operation->update(['amount' => '-10.00']);

    $response = $this->actingAs($this->owner)->get(route('portfolio.dashboard', $this->portfolio));

    $response
        ->assertOk()
        ->assertSee('Répartition analytique')
        ->assertDontSee(dangerousText(), escape: false);
    expect(substr_count($response->getContent(), '&lt;script&gt;alert('))->toBeGreaterThan(1);
});

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

test('the toasts escape the message of the session', function () {
    $this->actingAs($this->owner)
        ->withSession(['toast' => ['type' => 'success', 'message' => dangerousText()]])
        ->get(route('portfolio.index'))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(', escape: false)
        ->assertDontSee(dangerousText(), escape: false);
});

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

test('the ledger escapes the label search it shows again', function () {
    $response = $this->actingAs($this->owner)->get(route('ledger.index', [
        $this->portfolio,
        'label' => '"><b onmouseover=alert(1)>introuvable',
    ]));

    $response
        ->assertOk()
        ->assertSee('value="&quot;&gt;&lt;b onmouseover=alert(1)&gt;introuvable"', escape: false)
        ->assertDontSee('<b onmouseover', escape: false);
});

test('the ledger export neutralizes the formulas typed by the user', function () {
    $this->operation->update(['label' => '=HYPERLINK("https://example.com")']);

    $content = $this->actingAs($this->owner)->get(route('ledger.export', $this->portfolio))->streamedContent();

    expect($content)->toContain("\"'=HYPERLINK(\"\"https://example.com\"\")\"")
        ->not->toContain(';=HYPERLINK');
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

test('the server status escapes the name and the queue of a failed job', function () {
    DB::table('failed_jobs')->insert([
        'uuid' => (string) str()->uuid(),
        'connection' => 'database',
        'queue' => dangerousText(),
        'payload' => json_encode(['displayName' => dangerousText()]),
        'exception' => 'RuntimeException',
        'failed_at' => now(),
    ]);

    $response = $this
        ->actingAs(User::factory()->admin()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.server'));

    $response
        ->assertOk()
        ->assertSee('&lt;script&gt;', escape: false)
        ->assertDontSee(dangerousText(), escape: false);
});

test('the avatar escapes the initials', function () {
    $user = User::factory()->make(['first_name' => '<b>', 'last_name' => '&']);

    $view = $this->blade('<x-avatar :user="$user"/>', ['user' => $user]);

    $view->assertSee('>&lt;&amp;</span>', escape: false)->assertDontSee('<&', escape: false);
});
