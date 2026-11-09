<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('the former portfolio address redirects to the portfolio dashboard', function () {
    Mail::fake();
    $user = User::factory()->create();
    $portfolio = $user->portfolios()->create(['name' => 'Portfolio']);

    $response = $this->actingAs($user)->get("/portfolio/{$portfolio->id}");

    $response->assertRedirect(route('portfolio.dashboard', $portfolio));
});

test('the former portfolio address returns 405 for an identifier that is not a number', function () {
    $response = $this->actingAs(User::factory()->create())->get('/portfolio/abc');

    $response->assertMethodNotAllowed();
});
