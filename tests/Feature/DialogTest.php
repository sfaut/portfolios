<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('the dialog is a card named by its title and described by its description', function () {
    $view = $this->blade(<<<'BLADE'
        <x-dialog id="archive-account" danger>
            <x-slot:title>Archiver le compte ?</x-slot:title>
            <x-slot:description>Il pourra être désarchivé.</x-slot:description>
            <x-slot:footer><button>Archiver</button></x-slot:footer>
        </x-dialog>
        BLADE);

    $view
        ->assertSeeInOrder([
            '<dialog',
            'id="archive-account"',
            'aria-labelledby="archive-account-title"',
            'aria-describedby="archive-account-description"',
            'ring-red-800',
            'id="archive-account-title"',
            'Archiver le compte ?',
            'id="archive-account-description"',
            'Il pourra être désarchivé.',
            '<footer',
        ], escape: false)
        ->assertDontSee('showModal');
});

test('the dialog opens on page load when asked to', function () {
    $view = $this->blade('<x-dialog id="confirm" :show="true"><x-slot:title>Titre</x-slot:title></x-dialog>');

    $view->assertSee('x-init="$el.showModal()"', escape: false);
});

test('the destructive actions are confirmed in a dialog', function (string $route, string $dialog) {
    Mail::fake();

    $user = User::factory()->create();
    $portfolio = $user->portfolios()->create(['name' => 'Portfolio']);
    $account = $portfolio->accounts()->create(['name' => 'Account']);
    $operation = $account->operations()->create([
        'label' => 'Operation',
        'amount' => '10.00',
        'delivery_at' => '2026-01-15',
    ]);

    $parameters = [
        'portfolio.edit' => $portfolio,
        'account.edit' => $account,
        'operation.edit' => $operation,
        'profile.edit' => [],
    ][$route];

    $response = $this->actingAs($user)->get(route($route, $parameters));

    $response->assertOk()
        ->assertSeeInOrder([
            "commandfor=\"{$dialog}\" command=\"show-modal\"",
            '<dialog',
            "id=\"{$dialog}\"",
            "commandfor=\"{$dialog}\" command=\"close\"",
        ], escape: false)
        ->assertDontSee('confirm(');
})->with([
    ['portfolio.edit', 'archive-portfolio'],
    ['account.edit', 'archive-account'],
    ['operation.edit', 'delete-operation'],
    ['profile.edit', 'delete-user'],
]);
