<?php

use App\Enums\ColorScheme;
use App\Models\User;
use App\Support\UserDataExport;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('a new user follows the system', function () {
    expect($this->user->refresh()->color_scheme)->toBe(ColorScheme::System);
});

test('the chosen scheme is saved on the profile and set on the next pages', function (string $scheme, string $class) {
    $this->actingAs($this->user)
        ->putJson(route('profile.color-scheme'), ['color_scheme' => $scheme])
        ->assertNoContent();

    expect($this->user->refresh()->color_scheme->value)->toBe($scheme);

    $html = $this->get(route('portfolio.index'))->getContent();
    preg_match('/<html[^>]*>/', $html, $tag);
    expect($tag[0])
        ->toContain("data-color-scheme=\"{$scheme}\"")
        ->toContain('data-color-scheme-url="'.route('profile.color-scheme').'"');
    preg_match('/class="([^"]*)"/', $tag[0], $classes);
    expect(array_values(array_intersect(explode(' ', $classes[1]), ['light', 'dark'])))
        ->toBe($class === '' ? [] : [$class]);
})->with([
    'light' => ['light', 'light'],
    'dark' => ['dark', 'dark'],
    'system' => ['system', ''],
]);

test('a form sending the scheme comes back to its page', function () {
    $response = $this->actingAs($this->user)
        ->from(route('portfolio.index'))
        ->put(route('profile.color-scheme'), ['color_scheme' => 'dark']);

    $response->assertRedirect(route('portfolio.index'))->assertSessionHas('status', 'color-scheme-updated');
    expect($this->user->refresh()->color_scheme)->toBe(ColorScheme::Dark);
});

test('an unknown scheme is refused', function (mixed $scheme) {
    $this->actingAs($this->user)
        ->putJson(route('profile.color-scheme'), ['color_scheme' => $scheme])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('color_scheme');

    expect($this->user->refresh()->color_scheme)->toBe(ColorScheme::System);
})->with([
    'unknown' => ['sepia'],
    'empty' => [''],
    'array' => [['dark']],
    'upper case' => ['DARK'],
]);

test('saving the scheme needs no password confirmation, but a signed-in user', function () {
    $this->actingAs($this->user)
        ->putJson(route('profile.color-scheme'), ['color_scheme' => 'dark'])
        ->assertNoContent();

    auth()->logout();
    $this->put(route('profile.color-scheme'), ['color_scheme' => 'light'])->assertRedirect(route('login'));
});

test('the pages of a guest carry no saved scheme', function () {
    $html = $this->get(route('login'))->getContent();

    expect($html)->not->toContain('data-color-scheme')
        ->toContain("localStorage.getItem('theme')");
});

test('the top bar holds the scheme switch, and the user menu no longer does', function () {
    $this->user->update(['color_scheme' => ColorScheme::Dark]);

    $response = $this->actingAs($this->user)->get(route('portfolio.index'));

    $response
        ->assertSeeInOrder(['aria-label="Thème"', 'value="light"', 'value="system"', 'value="dark"', 'checked', 'Déconnexion'], escape: false)
        ->assertSee('x-model="$store.theme.mode"', escape: false)
        ->assertDontSee('id="theme-label"', escape: false);
});

test('the data export holds the scheme', function () {
    $this->user->update(['color_scheme' => ColorScheme::Dark]);

    $csv = (new UserDataExport($this->user))->csvFiles()['utilisateur.csv'];

    expect($csv)->toContain('thème')->toContain(';dark;');
});
