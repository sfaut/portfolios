<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
    $this->portfolio = $this->user->portfolios()->create(['name' => 'Famille']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Compte courant']);
    $this->operation = $this->account->operations()->create(['label' => 'Loyer', 'amount' => '-10.00', 'delivery_at' => '2026-11-05']);
    $this->analytic_axis = $this->portfolio->analyticAxes()->create(['name' => 'Piano']);
    $this->actingAs($this->user);
});

/**
 * Lien de section marqué comme page courante dans la navigation du portfolio, ou null s'il n'y en a pas.
 */
function currentSection(string $html): ?string
{
    return preg_match('/<nav aria-label="Pages du portfolio".*?aria-current="page"[^>]*>([^<]+)<\/a>/s', $html, $matches) === 1
        ? $matches[1]
        : null;
}

test('every page of a portfolio shows its navigation with the current section', function (string $route, string $model, array $query, ?string $section) {
    $response = $this->get(route($route, [$this->{$model}, ...$query]));

    $response->assertOk()
        ->assertSee('aria-label="Pages du portfolio"', escape: false)
        ->assertSee('<span class="sr-only">Portfolio : </span>Famille', escape: false);
    expect(currentSection($response->getContent()))->toBe($section);
})->with([
    ['portfolio.dashboard', 'portfolio', [], 'Tableau de bord'],
    ['portfolio.show', 'portfolio', [], 'Comptes'],
    ['portfolio.edit', 'portfolio', [], null],
    ['account.create', 'portfolio', [], 'Comptes'],
    ['account.show', 'account', ['page' => 1], 'Comptes'],
    ['account.edit', 'account', [], 'Comptes'],
    ['operation.create', 'account', [], 'Comptes'],
    ['operation.edit', 'operation', [], 'Comptes'],
    ['ledger.index', 'portfolio', [], 'Grand livre'],
    ['analytic-axis.index', 'portfolio', [], 'Axes analytiques'],
    ['analytic-axis.create', 'portfolio', [], 'Axes analytiques'],
    ['analytic-axis.edit', 'analytic_axis', [], 'Axes analytiques'],
]);

test('the pages of an archived account keep the navigation of its portfolio', function () {
    $this->account->delete();

    $response = $this->get(route('account.show', [$this->account, 'page' => 1]));

    expect(currentSection($response->getContent()))->toBe('Comptes');
});

test('the pages outside a portfolio have no portfolio navigation', function (string $route) {
    $response = $this->withSession(['auth.password_confirmed_at' => time()])->get(route($route));

    $response->assertOk()
        ->assertDontSee('aria-label="Pages du portfolio"', escape: false)
        ->assertDontSee('Portfolio : ');
})->with(['portfolio.index', 'portfolio.create', 'profile.edit', 'profile.preferences', 'profile.password', 'profile.account']);

test('the portfolio selector lists the portfolios of the user only, with the actions on portfolios', function () {
    $this->user->portfolios()->create(['name' => 'Association']);
    User::factory()->create()->portfolios()->create(['name' => 'Portfolio du voisin']);

    $response = $this->get(route('portfolio.dashboard', $this->portfolio));

    $response->assertSeeInOrder(['Association', 'Famille', '(portfolio affiché)', 'Modifier ce portfolio', 'Tous les portfolios', 'Créer un portfolio'])
        ->assertSee('href="'.route('portfolio.edit', $this->portfolio).'"', escape: false)
        ->assertDontSee('Portfolio du voisin');
});

test('the breadcrumb leads from the section to the parent page', function () {
    $response = $this->get(route('operation.edit', $this->operation));

    $response->assertSeeInOrder([
        '<nav aria-label="Fil d&#039;Ariane"',
        'href="'.route('portfolio.show', $this->portfolio).'"',
        'Comptes',
        'href="'.route('account.show', $this->account).'"',
        'Compte courant',
        '</nav>',
    ], escape: false);
});

test('the simple entries of the user menu have an icon', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('portfolio.index'));

    foreach (['Profil', 'État du serveur', 'Déconnexion'] as $entry) {
        expect($response->getContent())->toMatch('/<svg[^>]*aria-hidden="true"[^>]*>.*?<\/svg>\s*'.preg_quote($entry, '/').'\s*<\/a>/su');
    }
});
