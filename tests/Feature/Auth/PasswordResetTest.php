<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('a reset link request gives the same answer whether the account exists or not', function (bool $account_exists) {
    Notification::fake();
    $user = User::factory()->create(['email' => 'connu@example.com']);
    $email = $account_exists ? $user->email : 'inconnu@example.com';

    $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $email]);

    $response
        ->assertRedirect('/forgot-password')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Si un compte correspond à cette adresse, il recevra un e-mail avec un lien de réinitialisation.');
})->with([
    'existing account' => true,
    'unknown address' => false,
]);

test('a reset link request answered recently gives the same answer again', function () {
    Notification::fake();
    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Si un compte correspond à cette adresse, il recevra un e-mail avec un lien de réinitialisation.');
});

test('a password reset gives the same error for an unknown address as for an invalid link', function (string $email) {
    User::factory()->create(['email' => 'connu@example.com']);

    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'email' => "Ce lien de réinitialisation n'est pas valide pour cette adresse e-mail.",
    ]);
})->with([
    'existing account' => 'connu@example.com',
    'unknown address' => 'inconnu@example.com',
]);

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
