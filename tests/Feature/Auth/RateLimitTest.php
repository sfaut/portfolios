<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('the registration beyond five per hour and IP comes back with a message, without registering', function () {
    $registration = ['last_name' => 'User', 'first_name' => 'Test', 'password' => 'password', 'password_confirmation' => 'password'];
    foreach (range(1, 5) as $attempt) {
        $this->post(route('register'), [...$registration, 'email' => "personne{$attempt}@example.com"]);
    }

    $response = $this->from(route('register'))->post(route('register'), [...$registration, 'email' => 'nouvelle@example.com']);

    $response
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['email' => 'Trop de tentatives, réessayez dans 60 minutes.']);
    expect(User::where('email', 'nouvelle@example.com')->exists())->toBeFalse();
});

test('the reset link request beyond ten per hour and IP comes back with a message, without sending', function () {
    $user = User::factory()->create();
    foreach (range(1, 10) as $attempt) {
        $this->post(route('password.email'), ['email' => "personne{$attempt}@example.com"]);
    }

    $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email]);

    $response
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email' => 'Trop de tentatives, réessayez dans 60 minutes.']);
    Notification::assertNothingSentTo($user);
});

test('the login beyond its limit per IP comes back with a message, even for another address', function () {
    foreach (range(1, 20) as $attempt) {
        $this->post(route('login'), ['email' => "personne{$attempt}@example.com", 'password' => 'wrong-password']);
    }
    $user = User::factory()->create();

    $response = $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $response->assertSessionHasErrors(['email' => 'Trop de tentatives, réessayez dans une minute.']);
    $this->assertGuest();
});

test('a form checking the current password beyond its limit comes back with a message in its bag', function (string $method, string $route, string $bag, string $field, array $data) {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    foreach (range(1, 5) as $attempt) {
        $this->from('/profile')->{$method}(route($route), $data);
    }

    $response = $this->from('/profile')->{$method}(route($route), $data);

    $response
        ->assertRedirect('/profile')
        ->assertSessionHasErrorsIn($bag, [$field => 'Trop de tentatives, réessayez dans une minute.']);
    $this->assertModelExists($user);
})->with([
    'password confirmation' => ['post', 'password.confirm', 'default', 'password', ['password' => 'wrong-password']],
    'password update' => ['put', 'password.update', 'updatePassword', 'current_password', ['current_password' => 'wrong-password', 'password' => 'new-password', 'password_confirmation' => 'new-password']],
    'account deletion' => ['delete', 'profile.destroy', 'userDeletion', 'password', ['password' => 'wrong-password']],
]);
