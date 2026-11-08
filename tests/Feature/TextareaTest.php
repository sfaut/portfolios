<?php

test('the textarea renders its content and the attributes of the caller', function () {
    $view = $this->blade('<x-textarea id="label" name="label">Compte courant</x-textarea>');

    $view->assertSee('<textarea', escape: false)
        ->assertSee('name="label"', escape: false)
        ->assertSee('rows="4"', escape: false)
        ->assertSee('>Compte courant</textarea>', escape: false);
});

test('the textarea takes the number of rows given by the caller', function () {
    $view = $this->blade('<x-textarea name="label" rows="2"></x-textarea>');

    $view->assertSee('rows="2"', escape: false)
        ->assertDontSee('rows="4"', escape: false);
});
