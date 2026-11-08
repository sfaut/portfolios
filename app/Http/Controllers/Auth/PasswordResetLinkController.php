<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Demande d'un lien de réinitialisation du mot de passe (« Mot de passe oublié ? »).
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Affiche le formulaire de demande du lien.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Envoie le lien si un compte correspond à l'adresse.
     *
     * La réponse est la même que l'adresse ait un compte ou non, et qu'un lien soit déjà parti ou non :
     * elle ne dit pas si un compte existe.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
