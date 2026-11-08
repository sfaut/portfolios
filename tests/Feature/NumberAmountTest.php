<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;

test('an amount is written in French, with the minus sign when negative', function (int|float|string $number, string $expected) {
    expect(Number::amount($number))->toBe($expected);
})->with([
    'negative' => ['-1234.5', "−1\u{202F}234,50"],
    'zero' => ['0.00', '0,00'],
    'positive' => [42, '42,00'],
]);

test('a signed amount carries the plus sign when positive', function (int|float|string $number, string $expected) {
    expect(Number::amount($number, signed: true))->toBe($expected);
})->with([
    'negative' => ['-42.50', '−42,50'],
    'zero' => ['0.00', '0,00'],
    'positive' => ['42.50', '+42,50'],
]);

test('an amount can be followed by its currency', function () {
    expect(Number::amount('-42.5', currency: 'EUR'))->toBe("−42,50\u{A0}€");
});

test('the balances of an account are signed', function () {
    Mail::fake();

    $user = User::factory()->create();
    $account = $user->portfolios()->create(['name' => 'Portfolio'])
        ->accounts()->create(['name' => 'Account']);

    foreach (['10.00', '-25.00'] as $day => $amount) {
        $account->operations()->create([
            'label' => "Operation {$day}",
            'amount' => $amount,
            'delivery_at' => '2026-01-1'.($day + 1),
        ]);
    }

    $response = $this->actingAs($user)->get(route('account.show', [$account, 'page' => 1]));

    $response->assertOk()
        ->assertSeeInOrder(['10,00', '+10,00', '−25,00', '−15,00'])
        ->assertDontSee('-25,00');
});
