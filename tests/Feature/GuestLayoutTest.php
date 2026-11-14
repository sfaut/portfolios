<?php

use App\Models\User;

test('a guest page gives its title to the tab and to its heading', function (string $url, string $title) {
    $this->get($url)
        ->assertSee("<title>{$title} – Portfolios</title>", escape: false)
        ->assertSee("{$title}</h1>", escape: false);
})->with([
    'login' => ['/login', 'Connexion'],
    'forgot password' => ['/forgot-password', 'Mot de passe oublié'],
    'error page' => ['/no-such-page', 'Not Found'],
]);

test('an application page keeps the name of the application as its title', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('portfolio.index'))
        ->assertSee('<title>Portfolios</title>', escape: false);
});
