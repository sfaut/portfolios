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
        ->assertSee('2 comptes sur 4')
        ->assertDontSee('Compte courant')
        ->assertDontSee('Remise 100%');
});

test('the search takes the like wildcards literally', function (string $search, string $summary) {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', [$this->portfolio, 'search' => $search]));

    $response->assertOk()->assertSee($summary);
})->with([
    'percent' => ['%', '1 compte sur 4'],
    'underscore' => ['_', 'Aucun compte sur 4'],
]);

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
        ->assertDontSee('<h3>Créer un compte</h3>', escape: false)
        ->assertDontSee('Comptes archivés');
});

test('an ajax request receives the accounts fragment alone', function () {
    $response = $this->actingAs($this->owner)->get(
        route('portfolio.show', [$this->portfolio, 'search' => 'livret']),
        ['X-Requested-With' => 'XMLHttpRequest'],
    );

    $response->assertOk()
        ->assertHeader('Vary', 'X-Requested-With')
        ->assertSee('Livret Épargne')
        ->assertSee('data-summary', escape: false)
        ->assertDontSee('<html', escape: false)
        ->assertDontSee('Rechercher un compte');
});
