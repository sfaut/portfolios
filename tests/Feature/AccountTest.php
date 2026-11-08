<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
    $this->portfolio = $this->user->portfolios()->create(['name' => 'Portfolio']);
});

test('an account is created with its description', function () {
    $response = $this
        ->actingAs($this->user)
        ->post(route('account.store', $this->portfolio), [
            'name' => 'Livret A',
            'description' => 'Épargne de précaution',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('portfolio.show', $this->portfolio));

    $this->assertDatabaseHas('accounts', [
        'portfolio_id' => $this->portfolio->id,
        'name' => 'Livret A',
        'description' => 'Épargne de précaution',
    ]);
});

test('the description of an account is updated and shown again for edition', function () {
    $account = $this->portfolio->accounts()->create(['name' => 'Livret A']);

    $this
        ->actingAs($this->user)
        ->put(route('account.update', $account), [
            'name' => 'Livret A',
            'description' => 'Épargne de précaution',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.show', $account));

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'description' => 'Épargne de précaution',
    ]);

    $this
        ->actingAs($this->user)
        ->get(route('account.edit', $account))
        ->assertOk()
        ->assertSee('Épargne de précaution');
});
