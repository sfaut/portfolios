<?php

dataset('icon buttons', [
    'primary link' => ['<x-button variant="primary" href="/account/create" icon="plus">Créer</x-button>', '<x-button variant="primary" href="/account/create">Créer</x-button>'],
    'secondary link' => ['<x-button href="/account/1/edit" icon="pencil">Modifier</x-button>', '<x-button href="/account/1/edit">Modifier</x-button>'],
    'primary button' => ['<x-button variant="primary" type="submit" icon="check">Modifier</x-button>', '<x-button variant="primary" type="submit">Modifier</x-button>'],
    'secondary button' => ['<x-button icon="plus">Ajouter</x-button>', '<x-button>Ajouter</x-button>'],
]);

test('a link or a button shows its icon before its text, hidden from screen readers', function (string $with_icon, string $without_icon) {
    $this->blade($with_icon)
        ->assertSeeInOrder(['gap-2', '<svg', 'aria-hidden="true"', '</svg>'], escape: false);

    $this->blade($without_icon)
        ->assertDontSee('<svg', escape: false)
        ->assertDontSee('gap-2', escape: false);
})->with('icon buttons');

test('a button keeps the attributes given by its caller', function () {
    $this->blade('<x-button variant="primary" href="/" class="w-full" data-test="home">Accueil</x-button>')
        ->assertSee('w-full', escape: false)
        ->assertSee('data-test="home"', escape: false);
});
