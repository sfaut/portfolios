<?php

use Illuminate\View\ViewException;

test('a switch has one native radio per option, the current one checked', function () {
    $view = $this->blade(<<<'BLADE'
        <x-switch label="Affichage" name="display" :options="['list' => 'Liste', 'grid' => ['Grille', 'layout-grid']]" value="grid" class="mt-2"/>
        BLADE);

    $view->assertSeeInOrder([
        '<fieldset',
        'aria-label="Affichage"',
        'mt-2',
        'w-[calc((100%-0.25rem)/2)]',
        'type="radio"', 'value="list"', 'name="display"',
        'Liste',
        'type="radio"', 'value="grid"', 'name="display"', 'checked',
        '<svg',
        '<span class="sr-only">Grille</span>',
    ], escape: false);
    expect(preg_match_all('/\schecked[\s>]/', (string) $view))->toBe(1);
});

test('the color scheme variant offers light, system and dark, with icons and spoken labels', function () {
    $view = $this->blade('<x-switch label="Thème" variant="color-scheme" value="system" model="$store.theme.mode"/>');

    $view->assertSeeInOrder([
        'w-[calc((100%-0.25rem)/3)]',
        'value="light"', 'x-model="$store.theme.mode"', '<svg', '<span class="sr-only">Clair</span>',
        'value="system"', 'checked', '<svg', '<span class="sr-only">Système</span>',
        'value="dark"', '<svg', '<span class="sr-only">Sombre</span>',
    ], escape: false);
    $view->assertDontSee('name=', escape: false);
});

test('the labels can show beside the icons', function () {
    $view = $this->blade('<x-switch label="Thème" variant="color-scheme" labels/>');

    $view->assertSee('<span class="">Clair</span>', escape: false)
        ->assertDontSee('<span class="sr-only">', escape: false);
    expect(preg_match_all('/\schecked[\s>]/', (string) $view))->toBe(0);
});

test('a switch refuses a number of options other than 2 or 3, and an unknown variant', function (string $blade) {
    $this->blade($blade);
})->with([
    'one option' => ['<x-switch label="X" :options="[\'a\' => \'A\']"/>'],
    'four options' => ['<x-switch label="X" :options="[\'a\' => \'A\', \'b\' => \'B\', \'c\' => \'C\', \'d\' => \'D\']"/>'],
    'unknown variant' => ['<x-switch label="X" variant="rainbow"/>'],
])->throws(ViewException::class);
