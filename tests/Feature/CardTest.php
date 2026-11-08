<?php

test('the card renders its title, its description and its content', function () {
    $view = $this->blade(<<<'BLADE'
        <x-card>
            <x-slot:title>Informations du compte</x-slot:title>
            <x-slot:description>Renseignez les informations du compte.</x-slot:description>
            <form></form>
        </x-card>
        BLADE);

    $view
        ->assertSeeInOrder(['<header', 'Informations du compte', 'Renseignez les informations du compte.', '<form'], escape: false)
        ->assertDontSee('ring-red-800');
});

test('the card has no header without a title or a description', function () {
    $view = $this->blade('<x-card><form></form></x-card>');

    $view->assertDontSee('<header', escape: false);
});

test('the danger card has a red ring', function () {
    $view = $this->blade('<x-card danger><form></form></x-card>');

    $view->assertSee('ring-2 ring-red-800 dark:ring-red-600');
});
