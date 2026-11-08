<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();

    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Portfolio']);
    $this->portfolio->accounts()->create(['name' => 'Compte courant']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Livret clos']);
    $this->operation = $this->account->operations()->create([
        'label' => 'Versement initial',
        'amount' => '10.00',
        'delivery_at' => '2026-01-15',
    ]);

    $this->account->delete();
});

test('archived accounts are listed in their own section of the portfolio', function () {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', $this->portfolio));

    $response->assertOk()
        ->assertSeeInOrder(['Compte courant', 'Créer un compte', 'Comptes archivés', '>1</span>', 'Livret clos'], escape: false);
});

test('the archived accounts section is hidden without archived account', function () {
    $this->account->restore();

    $response = $this->actingAs($this->owner)->get(route('portfolio.show', $this->portfolio));

    $response->assertOk()->assertDontSee('Comptes archivés');
});

test('the operations of an archived account can be read but not written', function () {
    $response = $this->actingAs($this->owner)->get(route('account.show', [$this->account, 'page' => 1]));

    $response->assertOk()
        ->assertSee('Versement initial')
        ->assertSee('Ce compte est archivé')
        ->assertDontSee(route('operation.create', $this->account))
        ->assertDontSee(route('operation.edit', $this->operation));
});

test('an archived account can be edited and offers to be restored', function () {
    $response = $this->actingAs($this->owner)->get(route('account.edit', $this->account));

    $response->assertOk()
        ->assertSee(route('account.restore', $this->account))
        ->assertSee('Désarchivage du compte')
        ->assertDontSee('Archivage du compte');
});

test('an archived account can be restored', function () {
    $response = $this->actingAs($this->owner)->patch(route('account.restore', $this->account));

    $response
        ->assertRedirect(route('account.show', $this->account))
        ->assertSessionHas('alert', 'Le compte « Livret clos » a été désarchivé.');

    expect($this->account->fresh()->trashed())->toBeFalse();
});

test('the operations of an archived account cannot be written', function (string $method, string $route, string $model) {
    $response = $this
        ->actingAs($this->owner)
        ->{$method}(route($route, $this->{$model}), [
            'label' => 'Versement',
            'amount' => '5.00',
            'delivery_at' => '2026-02-01',
        ]);

    $response->assertNotFound();

    $this->assertDatabaseHas('operations', [
        'id' => $this->operation->id,
        'label' => 'Versement initial',
    ]);
    $this->assertDatabaseCount('operations', 1);
})->with([
    ['get', 'operation.create', 'account'],
    ['post', 'operation.store', 'account'],
    ['get', 'operation.edit', 'operation'],
    ['put', 'operation.update', 'operation'],
    ['delete', 'operation.destroy', 'operation'],
]);

test('users cannot reach the archived account of another user', function (string $method, string $route) {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)->{$method}(route($route, $this->account));

    $response->assertNotFound();

    expect($this->account->fresh()->trashed())->toBeTrue();
})->with([
    ['get', 'account.show'],
    ['get', 'account.edit'],
    ['put', 'account.update'],
    ['patch', 'account.restore'],
]);
