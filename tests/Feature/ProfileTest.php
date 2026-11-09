<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withSession(['auth.password_confirmed_at' => time()]);
});

test('the profile asks to confirm the password first', function () {
    $this->flushSession();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profile');

    $response->assertRedirect(route('password.confirm'));
});

test('the profile opens once the password is confirmed', function () {
    $this->flushSession();
    $user = User::factory()->create();
    $this->actingAs($user)->get('/profile');

    $response = $this->post(route('password.confirm'), ['password' => 'password']);

    $response->assertRedirect('/profile');
    $this->get('/profile')->assertOk();
});

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'last_name' => 'User',
            'first_name' => 'Test',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('User', $user->last_name);
    $this->assertSame('Test', $user->first_name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('a new email address receives a verification link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile', [
        'last_name' => 'User',
        'first_name' => 'Test',
        'email' => 'nouvelle@example.com',
    ]);

    Notification::assertSentTo($user, VerifyEmail::class, fn ($notification, $channels, $notifiable) => $notifiable->email === 'nouvelle@example.com');
});

test('email verification status is unchanged when the email address is unchanged', function () {
    Notification::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'last_name' => 'User',
            'first_name' => 'Test',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
    Notification::assertNothingSent();
});

test('users cannot make themselves administrator', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'last_name' => 'User',
            'first_name' => 'Test',
            'email' => $user->email,
            'is_admin' => '1',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect($user->refresh()->is_admin)->toBeFalse();
});

test('users can delete their account, with all their data', function () {
    Mail::fake();
    $user = User::factory()->create();
    $user->portfolios()->create(['name' => 'Portfolio'])
        ->accounts()->create(['name' => 'Compte'])
        ->operations()->create(['label' => 'Opération', 'amount' => '10.00', 'delivery_at' => '2026-01-15']);

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($user);
    $this->assertDatabaseEmpty('portfolios');
    $this->assertDatabaseEmpty('accounts');
    $this->assertDatabaseEmpty('operations');
});

test('the administrator cannot delete their account', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this
        ->actingAs($admin)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', ['password' => 'Le compte administrateur ne peut pas être supprimé.'])
        ->assertRedirect('/profile');

    $this->assertAuthenticatedAs($admin);
    $this->assertModelExists($admin);
});

test('the profile of the administrator offers no account deletion', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('profile.account'));

    $response
        ->assertSee('Le compte administrateur ne peut pas être supprimé.')
        ->assertDontSee('id="delete-user"', escape: false);
});

test('the correct password must be provided to delete the account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertModelExists($user);
});
