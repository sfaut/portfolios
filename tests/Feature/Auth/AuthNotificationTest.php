<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

test('the password reset mail is in French', function () {
    $mail = (new ResetPassword('token'))->toMail(User::factory()->create());

    expect($mail->subject)->toBe('Réinitialisation de votre mot de passe')
        ->and((string) $mail->render())
        ->toContain('Réinitialiser le mot de passe')
        ->toContain('Ce lien de réinitialisation expirera dans 60 minutes.')
        ->toContain('Si le bouton « Réinitialiser le mot de passe » ne fonctionne pas')
        ->toContain('Cordialement,');
});

test('the email verification mail is in French', function () {
    $mail = (new VerifyEmail)->toMail(User::factory()->unverified()->create());

    expect($mail->subject)->toBe('Vérification de votre adresse e-mail')
        ->and((string) $mail->render())
        ->toContain('Cliquez sur le bouton ci-dessous pour vérifier votre adresse e-mail.')
        ->toContain('Vérifier mon adresse e-mail')
        ->toContain('Bonjour,');
});
