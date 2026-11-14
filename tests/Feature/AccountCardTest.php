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

test('an account card shows its description, the period of its operations and their number', function () {
    Mail::fake();

    $user = User::factory()->create();
    $portfolio = $user->portfolios()->create(['name' => 'Portfolio']);
    $account = $portfolio->accounts()->create(['name' => 'Compte courant', 'description' => 'Banque du quartier']);
    $account->operations()->create(['label' => 'Loyer', 'amount' => '-700', 'delivery_at' => '2026-10-05']);
    $account->operations()->create(['label' => 'Salaire', 'amount' => '900', 'delivery_at' => '2026-11-02']);
    $portfolio->accounts()->create(['name' => 'Livret vide']);

    $this->actingAs($user)->get(route('portfolio.show', $portfolio))
        ->assertOk()
        ->assertSeeInOrder([
            'href="'.route('account.show', $account).'"', 'Compte courant', 'Banque du quartier', '+200,00',
            '05/10/2026 – 02/11/2026', '2 opérations',
            'Livret vide', 'Aucune opération',
        ], escape: false);
});
