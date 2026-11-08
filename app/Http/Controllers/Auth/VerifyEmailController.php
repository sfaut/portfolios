<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Lien de vérification de l'adresse, reçu par e-mail (URL signée).
 */
class VerifyEmailController extends Controller
{
    /**
     * Marque l'adresse de l'utilisateur connecté comme vérifiée, puis rouvre la page demandée.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('portfolio.index', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('portfolio.index', absolute: false).'?verified=1');
    }
}
