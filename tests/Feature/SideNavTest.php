<?php

test('the side navigation is named and shows the status of each link', function () {
    $view = $this->blade(<<<'BLADE'
        <x-side-nav label="Sections de test" class="mt-2">
            <x-side-nav.item href="#first" current status="ok">Première</x-side-nav.item>
            <x-side-nav.item href="#second" status="warning" :count="2">Seconde</x-side-nav.item>
            <x-side-nav.item href="#third" status="danger">Troisième</x-side-nav.item>
            <x-side-nav.item href="#fourth">Quatrième</x-side-nav.item>
        </x-side-nav>
        BLADE);

    $view->assertSeeInOrder([
        '<nav aria-label="Sections de test"',
        'mt-2',
        'href="#first"',
        'aria-current="true"',
        'bg-emerald-500',
        'Première',
        'href="#second"',
        'bg-amber-500',
        'Seconde',
        '(à surveiller)',
        '>2</span>',
        'href="#third"',
        'bg-red-600',
        'Troisième',
        '(anomalie)',
        'href="#fourth"',
        'Quatrième',
    ], escape: false);
    expect(substr_count((string) $view, 'aria-current'))->toBe(1)
        ->and(substr_count((string) $view, 'rounded-full'))->toBe(4);
});

test('the meter fills its share, bounded to the full gauge', function (float $value, string $width) {
    $view = $this->blade('<x-meter :value="$value" level="warning"/>', ['value' => $value]);

    $view->assertSee("width: {$width}%", escape: false)->assertSee('bg-amber-500');
})->with([
    'a share' => [0.426, '42.6'],
    'beyond full' => [1.5, '100'],
    'below empty' => [-0.2, '0'],
]);
