<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\User;
use App\Notifications\RegistrationAttempted;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/**
 * Champs d'inscription valides pour l'adresse donnée.
 *
 * @return array<string, string>
 */
function registration(string $email): array
{
    return [
        'last_name' => 'User',
        'first_name' => 'Test',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
}

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('the registration screen links to the login', function () {
    $response = $this->get('/register');

    $response->assertSee('href="'.route('login').'"', escape: false);
});

test('new users are registered without being logged in, and told to check their mailbox', function () {
    Notification::fake();

    $response = $this->post('/register', registration('test@example.com'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', RegisteredUserController::REGISTRATION_STATUS);
    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->sole())
        ->last_name->toBe('User')
        ->hasVerifiedEmail()->toBeFalse();
});

test('new users receive a link to verify their address', function () {
    Notification::fake();

    $this->post('/register', registration('test@example.com'));

    Notification::assertSentTo(User::where('email', 'test@example.com')->sole(), VerifyEmail::class);
});

test('registering an address that has an account gives the same answer and changes nothing', function () {
    Notification::fake();
    $user = User::factory()->create([
        'last_name' => 'Martin',
        'email' => 'test@example.com',
        'password' => Hash::make('ancien mot de passe'),
    ]);

    $response = $this->post('/register', registration('test@example.com'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', RegisteredUserController::REGISTRATION_STATUS);
    $this->assertGuest();
    expect(User::sole())
        ->last_name->toBe('Martin')
        ->and(Hash::check('ancien mot de passe', $user->fresh()->password))->toBeTrue();
});

test('the holder of an address that has an account is told of the registration attempt', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->post('/register', registration('test@example.com'));

    Notification::assertSentTo($user, RegistrationAttempted::class);
    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('the registration attempt mail sends its holder to the login page', function () {
    $mail = (new RegistrationAttempted)->toMail(User::factory()->create());

    expect($mail->subject)->toBe('Tentative d\'inscription avec votre adresse')
        ->and($mail->actionUrl)->toBe(route('login'));
});

test('new users cannot register as administrator', function () {
    Notification::fake();

    $this->post('/register', [...registration('test@example.com'), 'is_admin' => '1']);

    expect(User::where('email', 'test@example.com')->sole()->is_admin)->toBeFalse();
});
