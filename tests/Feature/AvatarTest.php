<?php

use App\Models\User;

test('the initials take the first letter of the first name, then of the last name', function (array $names, string $initials) {
    expect(User::factory()->make($names)->initials())->toBe($initials);
})->with([
    'first and last names' => [['first_name' => 'Sébastien', 'last_name' => 'Faut'], 'SF'],
    'accents kept, capitalized' => [['first_name' => 'élodie', 'last_name' => 'durand'], 'ÉD'],
    'compound first name' => [['first_name' => 'Jean-Pierre', 'last_name' => 'Œuvray'], 'JŒ'],
    'first name only' => [['first_name' => 'Sim', 'last_name' => ''], 'S'],
    'last name only' => [['first_name' => '  ', 'last_name' => 'One'], 'O'],
    'no name' => [['first_name' => '', 'last_name' => '', 'email' => 'zoé@example.com'], 'Z'],
]);

test('the avatar is a round badge with the initials, hidden from screen readers', function (string $size, string $classes) {
    $user = User::factory()->make(['first_name' => 'Sébastien', 'last_name' => 'Faut']);

    $view = $this->blade('<x-avatar :user="$user" :size="$size" class="ml-2"/>', ['user' => $user, 'size' => $size]);

    $view->assertSeeInOrder(['<span', 'aria-hidden="true"', 'rounded-full', $classes, 'ml-2', '>SF</span>'], escape: false);
})->with([
    'small' => ['sm', 'size-8 text-xs'],
    'medium' => ['md', 'size-12 text-base'],
    'large' => ['lg', 'size-32 text-4xl'],
]);

test('the user menu opens on the avatar, with the name spoken on a narrow screen', function () {
    $user = User::factory()->create(['first_name' => 'Sim', 'last_name' => 'One']);

    $response = $this->actingAs($user)->get(route('portfolio.index'));

    $response->assertSeeInOrder(['aria-label="Thème"', '>SO</span>', '<span class="sr-only sm:hidden">Sim One</span>', 'Profil'], escape: false);
});
