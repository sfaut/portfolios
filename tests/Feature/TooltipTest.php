<?php

test('the tooltip describes its focusable trigger with its content', function () {
    $view = $this->blade(<<<'BLADE'
        <x-tooltip class="ml-1">
            <span>2 axes</span>
            <x-slot:content>Cours piano 50 %</x-slot:content>
        </x-tooltip>
        BLADE);

    $view->assertSeeInOrder([
        'x-id="[\'tooltip\']"',
        'x-bind:aria-describedby="$id(\'tooltip\')"',
        'tabindex="0"',
        'ml-1',
        '2 axes',
        'x-bind:id="$id(\'tooltip\')"',
        'popover="manual"',
        'role="tooltip"',
        'bg-gray-950',
        'Cours piano 50 %',
    ], escape: false);
});

test('the tooltip opens on hover and focus, and closes on leave, blur and Escape', function () {
    $view = $this->blade('<x-tooltip>Déclencheur<x-slot:content>Détail</x-slot:content></x-tooltip>');

    $view->assertSee([
        'x-on:mouseenter="$refs.tooltip.togglePopover(true)"',
        'x-on:focusin="$refs.tooltip.togglePopover(true)"',
        'x-on:mouseleave="$refs.tooltip.togglePopover(false)"',
        'x-on:focusout="$refs.tooltip.togglePopover(false)"',
        'x-on:keydown.escape="$refs.tooltip.togglePopover(false)"',
    ], escape: false);
});
