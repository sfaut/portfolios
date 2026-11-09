<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('the segmented navigation is named and marks the current page only', function () {
    $view = $this->blade(<<<'BLADE'
        <x-segmented-nav label="Pages de test" class="mt-2">
            <x-segmented-nav.item href="/first" current>Première</x-segmented-nav.item>
            <x-segmented-nav.item href="/second">Seconde</x-segmented-nav.item>
        </x-segmented-nav>
        BLADE);

    $view
        ->assertSeeInOrder([
            '<nav aria-label="Pages de test"',
            'mt-2',
            'href="/first"',
            'aria-current="page"',
            'shadow-sm',
            'Première',
            'href="/second"',
            'Seconde',
        ], escape: false);
    expect(substr_count((string) $view, 'aria-current'))->toBe(1);
});

test('the portfolio pages mark their own page in the navigation', function (string $route, string $page) {
    Mail::fake();
    $user = User::factory()->create();
    $portfolio = $user->portfolios()->create(['name' => 'Portfolio']);

    $response = $this->actingAs($user)->get(route($route, $portfolio));

    $response->assertOk()->assertSee('aria-current="page"', escape: false);
    expect($response->getContent())->toMatch('/<a\s+href="[^"]*"\s+aria-current="page"[^>]*>'.$page.'<\/a>/');
})->with([
    ['portfolio.dashboard', 'Tableau de bord'],
    ['portfolio.show', 'Comptes'],
    ['analytic-axis.index', 'Axes analytiques'],
]);
