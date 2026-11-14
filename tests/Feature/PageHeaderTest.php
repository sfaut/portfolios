<?php

test('a page header puts the breadcrumb above the title, the description below it and the actions after', function () {
    $this->blade(
        '<x-page-header :breadcrumb="[\'/comptes\' => \'Comptes\']">Compte courant'
        .'<x-slot:description>Les 24 opérations du compte.</x-slot:description>'
        .'<x-slot:actions><a href="/modifier">Modifier le compte</a></x-slot:actions>'
        .'</x-page-header>'
    )->assertSeeInOrder([
        "aria-label=\"Fil d'Ariane\"", 'href="/comptes"', 'Comptes',
        '<h2', 'Compte courant', '</h2>',
        '<p', 'Les 24 opérations du compte.', '</p>',
        'href="/modifier"',
    ], escape: false);
});

test('a page header leaves out the rows it is not given', function () {
    $this->blade('<x-page-header>Grand livre</x-page-header>')
        ->assertSee('Grand livre</h2>', escape: false)
        ->assertDontSee("Fil d'Ariane", escape: false)
        ->assertDontSee('<p', escape: false)
        ->assertDontSee('gap-3', escape: false);
});
