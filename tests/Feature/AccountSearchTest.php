<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();

    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Portfolio']);
    $this->portfolio->accounts()->create(['name' => 'Compte courant']);
    $this->portfolio->accounts()->create(['name' => 'Livret Épargne']);
    $this->portfolio->accounts()->create(['name' => 'Remise 100%']);
    $this->portfolio->accounts()->create(['name' => 'Épargne logement'])->delete();
});

test('the search filters active and archived accounts by name, regardless of case and accents', function () {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'search' => 'EPARGNE']));

    $response->assertOk()
        ->assertSeeInOrder(['Livret Épargne', 'Comptes archivés', 'Épargne logement'])
        ->assertDontSee('Compte courant')
        ->assertDontSee('Remise 100%');

    expect(summaryFigures($response->getContent()))->toBe(['Comptes' => '1 sur 3', 'Comptes archivés' => '1 sur 1']);
});

test('the search takes the like wildcards literally', function (string $search, string $found) {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'search' => $search]));

    $response->assertOk();

    expect(summaryFigures($response->getContent()))->toBe(['Comptes' => $found, 'Comptes archivés' => '0 sur 1']);
})->with([
    'percent' => ['%', '1 sur 3'],
    'underscore' => ['_', '0 sur 3'],
]);

test('a search sent as a list is ignored', function () {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'search' => ['livret']]));

    $response->assertOk()
        ->assertSee('Compte courant')
        ->assertSee('Livret Épargne');

    expect(summaryFigures($response->getContent()))->toBe(['Comptes' => '3', 'Comptes archivés' => '1']);
});

test('the search opens the archived accounts section when it finds archived accounts', function () {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'search' => 'logement']));

    $response->assertOk()
        ->assertSee('x-data="{ open: true }"', escape: false)
        ->assertSee('Épargne logement');
});

test('a search without result says so and hides the account creation card', function () {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'search' => '<b>Assurance</b>']));

    $response->assertOk()
        ->assertSee('Aucun compte ne correspond à « &lt;b&gt;Assurance&lt;/b&gt; ».', escape: false)
        ->assertDontSee('<b>Assurance</b>', escape: false)
        ->assertDontSee('<span>Créer un compte</span>', escape: false)
        ->assertDontSee('aria-controls="archived-accounts"', escape: false);
});

test('an ajax request receives the accounts fragment alone', function () {
    $response = $this->actingAs($this->owner)->get(
        route('portfolio.show', [$this->portfolio, 'search' => 'livret']),
        ['X-Requested-With' => 'XMLHttpRequest'],
    );

    $response->assertOk()
        ->assertHeader('Vary', 'X-Requested-With')
        ->assertSee('Livret Épargne')
        ->assertDontSee('<html', escape: false)
        ->assertDontSee('Rechercher un compte');

    expect(summaryFigures($response->getContent()))->toBe(['Comptes' => '1 sur 3', 'Comptes archivés' => '0 sur 1']);
});

/**
 * Ajoute une opération au compte du portfolio de test qui porte ce nom, archivé ou non. Les portées
 * sont levées : hors requête, aucun utilisateur n'est connecté.
 *
 * @param  string  $account_name  Nom du compte.
 * @param  string  $delivery_at  Date effective de l'opération (« 2026-10-31 »).
 */
function addOperationTo(string $account_name, string $delivery_at): void
{
    test()->portfolio->accounts()->withoutGlobalScopes()->where('name', $account_name)->sole()
        ->operations()->create(['label' => 'Opération', 'amount' => '10.00', 'delivery_at' => $delivery_at]);
}

test('the month filter keeps the accounts with an operation in the month, archived ones included', function () {
    addOperationTo('Compte courant', '2026-10-31');
    addOperationTo('Livret Épargne', '2026-11-01');
    addOperationTo('Épargne logement', '2026-10-01');

    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'month' => '2026-10']));

    $response->assertOk()
        ->assertSeeInOrder(['Compte courant', 'Comptes archivés', 'Épargne logement'])
        ->assertDontSee('Livret Épargne')
        ->assertDontSee('Remise 100%');

    expect(summaryFigures($response->getContent()))->toBe(['Comptes' => '1 sur 3', 'Comptes archivés' => '1 sur 1']);
});

test('the month filter offers the months of the operations', function () {
    addOperationTo('Compte courant', '2026-10-31');
    addOperationTo('Livret Épargne', '2026-11-01');
    addOperationTo('Épargne logement', '2025-12-24');

    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'month' => '2026-10']));

    $response->assertOk()
        ->assertSee('\u0022allow\u0022:[[\u00222025-12\u0022,\u00222025-12\u0022],[\u00222026-10\u0022,\u00222026-10\u0022],[\u00222026-11\u0022,\u00222026-11\u0022]]', escape: false)
        ->assertSee('<input type="hidden" name="month" value="2026-10"', escape: false)
        ->assertSee('Octobre 2026');
});

test('a month refused by the month restriction is ignored', function (mixed $month) {
    addOperationTo('Compte courant', '2026-10-31');

    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'month' => $month]));

    $response->assertOk()
        ->assertSee('Livret Épargne')
        ->assertSee('<span>Créer un compte</span>', escape: false)
        ->assertDontSee('sur 3');
})->with([
    'without operation' => ['2026-09'],
    'malformed' => ['2026-10-01'],
    'list' => [['2026-10']],
]);

test('the search and the month filter add up', function () {
    addOperationTo('Compte courant', '2026-10-31');
    addOperationTo('Livret Épargne', '2026-11-01');

    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'search' => 'livret', 'month' => '2026-10']));

    $response->assertOk()
        ->assertSee('Aucun compte ne correspond à « livret » avec une opération en octobre 2026.', escape: false)
        ->assertDontSee('Compte courant');
});
