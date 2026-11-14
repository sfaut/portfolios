<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Changement du mot de passe depuis le profil.
 */
class PasswordController extends Controller
{
    /**
     * Remplace le mot de passe, après vérification du mot de passe actuel.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = Validator::make(
            data: $request->all(),
            rules: [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ],
            attributes: [
                'current_password' => 'Mot de passe actuel',
                'password' => 'Nouveau mot de passe',
            ],
        )->validateWithBag('updatePassword');

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
