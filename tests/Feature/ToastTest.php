<?php

use App\Models\User;
use App\View\Components\ToastRegion;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the layout holds the region of the toasts, without toast to show', function () {
    $this->get(route('portfolio.index'))
        ->assertOk()
        ->assertSee('aria-label="Notifications"', escape: false)
        ->assertSee('$store.toasts.mount($el, \'bottom-right\', null)', escape: false)
        ->assertDontSee('<noscript>', escape: false);
});

test('the toast flash of the session is shown, with and without JavaScript', function () {
    $this->withSession(['toast' => ['type' => 'success', 'message' => 'Opération pointée']])
        ->get(route('portfolio.index'))
        ->assertSee('\u0022type\u0022:\u0022success\u0022', escape: false)
        ->assertSee('Opération pointée')
        ->assertSee('<noscript>', escape: false);
});

test('the toast flash may be its message alone, an unknown type falling back to info', function (mixed $flash) {
    $region = new ToastRegion;

    expect($region->flash)->toBeNull();

    session()->flash('toast', $flash);

    expect((new ToastRegion)->flash)->toBe(['type' => 'info', 'message' => 'Enregistré']);
})->with([
    'message' => 'Enregistré',
    'unknown type' => [['type' => 'error', 'message' => 'Enregistré']],
    'type of another kind' => [['type' => ['danger'], 'message' => 'Enregistré']],
]);

test('a flash without message shows no toast', function (mixed $flash) {
    session()->flash('toast', $flash);

    expect((new ToastRegion)->flash)->toBeNull();
})->with([
    'empty' => '',
    'no message' => [['type' => 'success']],
    'message of another kind' => [['message' => ['Enregistré']]],
]);

test('an unknown place falls back to the bottom right corner', function () {
    expect((new ToastRegion('top-center'))->position)->toBe('top-center')
        ->and((new ToastRegion('middle'))->position)->toBe('bottom-right');
});
