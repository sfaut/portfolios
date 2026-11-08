<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'last_name' => 'User',
        'first_name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('portfolio.index', absolute: false));
});

test('new users receive a link to verify their address', function () {
    Notification::fake();

    $this->post('/register', [
        'last_name' => 'User',
        'first_name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->sole();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('new users cannot register as administrator', function () {
    $response = $this->post('/register', [
        'last_name' => 'User',
        'first_name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_admin' => '1',
    ]);

    $response->assertRedirect(route('portfolio.index', absolute: false));

    expect(User::where('email', 'test@example.com')->sole()->is_admin)->toBeFalse();
});
