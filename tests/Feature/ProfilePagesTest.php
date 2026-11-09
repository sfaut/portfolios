<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()]);
});

test('each page of the profile marks itself in the side navigation', function (string $route, string $title, string $content) {
    $response = $this->get(route($route));

    $response->assertOk()
        ->assertSee("<h2 class=\"text-xl font-semibold\">{$title}</h2>", escape: false)
        ->assertSee($content);

    preg_match('/<nav aria-label="Pages du profil".*?<\/nav>/s', $response->getContent(), $navigation);
    expect($navigation[0])
        ->toContain('href="'.route('profile.edit').'"')
        ->toContain('href="'.route('profile.preferences').'"')
        ->toContain('href="'.route('profile.password').'"')
        ->toContain('href="'.route('profile.account').'"')
        ->and(substr_count($navigation[0], 'aria-current="page"'))->toBe(1)
        ->and($navigation[0])->toMatch('/href="'.preg_quote(route($route), '/').'"\s+aria-current="page"/');
})->with([
    ['profile.edit', 'Profil', 'Informations du profil'],
    ['profile.preferences', 'Préférences', 'Apparence'],
    ['profile.password', 'Mot de passe', 'Modification du mot de passe'],
    ['profile.account', 'Données et compte', 'Téléchargement des données'],
]);

test('every page of the profile asks to confirm the password first', function (string $route) {
    $this->flushSession();

    $this->get(route($route))->assertRedirect(route('password.confirm'));
})->with(['profile.edit', 'profile.preferences', 'profile.password', 'profile.account']);

test('the data and account page ends with the account deletion', function () {
    $this->get(route('profile.account'))
        ->assertSeeInOrder(['Téléchargement des données', 'Suppression de compte']);
});

test('the preferences page sends the scheme, also without JavaScript', function () {
    $this->user->update(['color_scheme' => 'dark']);

    $response = $this->get(route('profile.preferences'));

    $response->assertSeeInOrder([
        'action="'.route('profile.color-scheme').'"',
        'value="light"', 'name="color_scheme"',
        'value="dark"', 'checked',
        '<span class="">Sombre</span>',
        '<noscript>', 'Enregistrer',
    ], escape: false);
});

test('a saved scheme is confirmed on the preferences page', function () {
    $this->from(route('profile.preferences'))
        ->put(route('profile.color-scheme'), ['color_scheme' => 'light'])
        ->assertRedirect(route('profile.preferences'));

    $this->get(route('profile.preferences'))->assertSee('Thème enregistré.');
});

test('a password change comes back to the password page', function () {
    $this->from(route('profile.password'))
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertRedirect(route('profile.password'));
});
