<?php

dataset('icon components', [
    'primary link' => ['<x-link.primary target="/account/create" icon="plus">Créer</x-link.primary>', '<x-link.primary target="/account/create">Créer</x-link.primary>'],
    'secondary link' => ['<x-link.secondary target="/account/1/edit" icon="pencil">Modifier</x-link.secondary>', '<x-link.secondary target="/account/1/edit">Modifier</x-link.secondary>'],
    'primary button' => ['<x-primary-button icon="check">Modifier</x-primary-button>', '<x-primary-button>Modifier</x-primary-button>'],
    'secondary button' => ['<x-secondary-button icon="plus">Ajouter</x-secondary-button>', '<x-secondary-button>Ajouter</x-secondary-button>'],
]);

test('a link or a button shows its icon before its text, hidden from screen readers', function (string $with_icon, string $without_icon) {
    $this->blade($with_icon)
        ->assertSeeInOrder(['gap-2', '<svg', 'aria-hidden="true"', '</svg>'], escape: false);

    $this->blade($without_icon)
        ->assertDontSee('<svg', escape: false)
        ->assertDontSee('gap-2', escape: false);
})->with('icon components');

test('a primary link keeps the attributes given by its caller', function () {
    $this->blade('<x-link.primary target="/" class="w-full" data-test="home">Accueil</x-link.primary>')
        ->assertSee('w-full', escape: false)
        ->assertSee('data-test="home"', escape: false);
});
