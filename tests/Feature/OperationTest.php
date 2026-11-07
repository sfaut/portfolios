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
