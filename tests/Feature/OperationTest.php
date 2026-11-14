<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('an operation can be added to an account', function () {
    Mail::fake();

    $user = User::factory()->create();
    $account = $user->portfolios()->create(['name' => 'Portfolio'])
        ->accounts()->create(['name' => 'Account']);

    $response = $this
        ->actingAs($user)
        ->post(route('operation.store', $account), [
            'label' => 'Groceries',
            'amount' => '-42.50',
            'delivery_at' => '2026-11-07',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.show', $account))
        ->assertSessionHas('alert', 'Opération ajoutée avec succès.');

    $this->assertDatabaseHas('operations', [
        'account_id' => $account->id,
        'label' => 'Groceries',
        'amount' => '-42.50',
        'delivery_at' => '2026-11-07',
    ]);
});

test('the operation just updated is marked in the list of the account', function () {
    Mail::fake();

    $user = User::factory()->create();
    $account = $user->portfolios()->create(['name' => 'Portfolio'])
        ->accounts()->create(['name' => 'Account']);
    $account->operations()->create(['label' => 'Rent', 'amount' => '-800.00', 'delivery_at' => '2026-11-01']);
    $updated = $account->operations()->create(['label' => 'Groceries', 'amount' => '-42.50', 'delivery_at' => '2026-11-07']);

    $response = $this
        ->actingAs($user)
        ->withSession(['updated_operation_id' => $updated->id])
        ->get(route('account.show', [$account, 'page' => 1]));

    $response->assertOk()->assertSeeInOrder(['Rent', 'aria-current="true"', 'Groceries'], escape: false);
    expect(substr_count($response->getContent(), 'aria-current="true"'))->toBe(1);
});

test('the operation form follows the order of the columns of the account', function () {
    Mail::fake();

    $user = User::factory()->create();
    $account = $user->portfolios()->create(['name' => 'Portfolio'])
        ->accounts()->create(['name' => 'Account']);

    $this->actingAs($user)
        ->get(route('operation.create', $account))
        ->assertOk()
        ->assertSeeInOrder(['id="delivery_at"', 'id="label"', 'id="amount"', 'Répartition analytique', 'id="is_ticked"'], escape: false);
});
