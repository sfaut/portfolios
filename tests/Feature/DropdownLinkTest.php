<?php

test('a dropdown link shows its icon before its text', function () {
    $view = $this->blade('<x-dropdown-link href="/profile" icon="log-out" class="mt-2">Déconnexion</x-dropdown-link>');

    $view->assertSeeInOrder(['<a', 'flex items-center gap-2', 'mt-2', 'href="/profile"', '<svg', 'aria-hidden="true"', 'Déconnexion'], escape: false)
        ->assertDontSee('block w-full', escape: false);
});

test('a dropdown link without icon stays a block of text', function () {
    $view = $this->blade('<x-dropdown-link href="/profile">Profil</x-dropdown-link>');

    $view->assertSee('block', escape: false)->assertDontSee('<svg', escape: false);
});
