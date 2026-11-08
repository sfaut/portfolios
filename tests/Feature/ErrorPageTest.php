<?php

test('error pages use the application layout, which applies the chosen theme', function () {
    $response = $this->get('/no-such-page');

    $response
        ->assertNotFound()
        ->assertSeeText('Not Found')
        ->assertSee("localStorage.getItem('theme')", escape: false)
        ->assertSee("Retour à l'accueil", escape: false);
});
