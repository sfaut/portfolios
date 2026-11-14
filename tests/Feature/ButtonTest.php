<?php

use App\View\Components\Button;

test('a button is of type button unless its caller says otherwise', function () {
    $this->blade('<x-button>Ajouter</x-button>')
        ->assertSee('<button type="button"', escape: false);

    $this->blade('<x-button variant="primary" type="submit">Créer</x-button>')
        ->assertSee('<button type="submit"', escape: false);
});

test('a button with an address is a link, without a type', function () {
    $this->blade('<x-button variant="primary" href="/portfolio" type="submit">Portfolios</x-button>')
        ->assertSee('<a href="/portfolio"', escape: false)
        ->assertDontSee('<button', escape: false)
        ->assertDontSee('type=', escape: false);
});

test('each variant has its own colors', function (string $variant, string $color) {
    $this->blade("<x-button variant=\"{$variant}\">Action</x-button>")
        ->assertSee($color, escape: false);
})->with([
    'primary' => ['primary', 'bg-indigo-600'],
    'secondary' => ['secondary', 'bg-gray-100'],
    'danger' => ['danger', 'bg-red-600'],
]);

test('the default variant is secondary', function () {
    expect((new Button)->classes)->toBe([...Button::BASE, ...Button::VARIANTS['secondary']]);
});

test('an unknown variant is refused', function () {
    new Button(variant: 'primmary');
})->throws(InvalidArgumentException::class, 'primmary');
