<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Connexion et déconnexion.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Affiche le formulaire de connexion.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Connecte l'utilisateur, avec une nouvelle session, puis rouvre la page qu'il demandait, à défaut
     * la liste de ses portfolios.
     *
     * La page demandée est, par exemple, le lien de vérification reçu à l'inscription : la connexion
     * vérifie alors l'adresse sans second clic.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('portfolio.index', absolute: false));
    }

    /**
     * Déconnecte l'utilisateur, invalide sa session et renouvelle le jeton CSRF.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
