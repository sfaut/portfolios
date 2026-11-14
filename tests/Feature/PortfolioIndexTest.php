<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
});

test('a portfolio without description shows none', function (string $description) {
    $this->user->portfolios()->create(['name' => 'Portfolio', 'description' => $description]);

    $response = $this->actingAs($this->user)->get(route('portfolio.index'));

    $response->assertOk()
        ->assertSee('Portfolio')
        ->assertDontSee('line-clamp-2', escape: false)
        ->assertDontSee('>--</p>', escape: false);
})->with([
    'empty' => [''],
    'blank' => ['   '],
]);

test('a portfolio with a description shows it', function () {
    $this->user->portfolios()->create(['name' => 'Portfolio', 'description' => 'Épargne de précaution']);

    $response = $this->actingAs($this->user)->get(route('portfolio.index'));

    $response->assertOk()->assertSee('Épargne de précaution');
});

test('each portfolio card leads to its dashboard and counts its active accounts', function () {
    $portfolio = $this->user->portfolios()->create(['name' => 'Cours particuliers']);
    $portfolio->accounts()->create(['name' => 'Grjebine, Arthur']);
    $portfolio->accounts()->create(['name' => 'Martin, Léa']);
    $portfolio->accounts()->create(['name' => 'Ancien élève'])->delete();
    $this->user->portfolios()->create(['name' => 'Épargne']);

    $this->actingAs($this->user)->get(route('portfolio.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'href="'.route('portfolio.dashboard', $portfolio).'"', 'Cours particuliers', '2 comptes',
            'Épargne', 'Aucun compte',
            'Créer un portfolio',
        ], escape: false);
});
