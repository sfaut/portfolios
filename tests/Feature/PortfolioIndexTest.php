<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
});

test('a portfolio without description shows a dash', function (string $description) {
    $this->user->portfolios()->create(['name' => 'Portfolio', 'description' => $description]);

    $response = $this->actingAs($this->user)->get(route('portfolio.index'));

    $response->assertOk()->assertSee('>--</p>', escape: false);
})->with([
    'empty' => [''],
    'blank' => ['   '],
]);

test('a portfolio with a description shows it', function () {
    $this->user->portfolios()->create(['name' => 'Portfolio', 'description' => 'Épargne de précaution']);

    $response = $this->actingAs($this->user)->get(route('portfolio.index'));

    $response->assertOk()
        ->assertSee('Épargne de précaution')
        ->assertDontSee('>--</p>', escape: false);
});
