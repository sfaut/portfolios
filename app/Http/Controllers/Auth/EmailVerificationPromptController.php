<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page qui demande de vérifier l'adresse, où mène le middleware `verified`.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Affiche la demande de vérification, ou rouvre la page demandée si l'adresse est déjà vérifiée.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('portfolio.index', absolute: false))
            : view('auth.verify-email');
    }
}
