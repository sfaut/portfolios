<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Nouvel envoi du lien de vérification de l'adresse.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Envoie un nouveau lien de vérification, sauf à une adresse déjà vérifiée.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('portfolio.index', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
