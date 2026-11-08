<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('the balance of an account card carries its sign and color', function (array $amounts, string $balance, string $color) {
    Mail::fake();

    $user = User::factory()->create();
    $portfolio = $user->portfolios()->create(['name' => 'Portfolio']);
    $account = $portfolio->accounts()->create(['name' => 'Compte courant']);

    foreach ($amounts as $amount) {
        $account->operations()->create(['label' => 'Opération', 'amount' => $amount, 'delivery_at' => '2026-11-08']);
    }

    $response = $this->actingAs($user)->get(route('portfolio.show', $portfolio));

    $response->assertOk()->assertSeeInOrder(['Compte courant', $color, $balance], escape: false);
})->with([
    'positive' => [['42.50'], '+42,50', 'text-blue-700'],
    'negative' => [['-42.50'], '−42,50', 'text-red-700'],
    'zero' => [['10.00', '-10.00'], '0,00', 'text-emerald-700'],
    'no operation' => [[], '0,00', 'text-emerald-700'],
]);
