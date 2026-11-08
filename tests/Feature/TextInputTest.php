<?php

test('the text input is a text field by default', function () {
    $view = $this->blade('<x-text-input name="label" />');

    $view->assertSee('type="text"', escape: false);
});

test('the text input takes the type given by the caller, without a second type', function () {
    $view = $this->blade('<x-text-input type="password" name="password" />');

    $view
        ->assertSee('type="password"', escape: false)
        ->assertDontSee('type="text"', escape: false);
});
