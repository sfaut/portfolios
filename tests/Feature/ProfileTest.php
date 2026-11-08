<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

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

test('account deletion is disabled', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response->assertStatus(501);

    $this->assertNotNull($user->fresh());
});
