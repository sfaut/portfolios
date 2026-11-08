<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Inscription d'un nouvel utilisateur.
 */
class RegisteredUserController extends Controller
{
    /**
     * Affiche le formulaire d'inscription.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Crée l'utilisateur, lui envoie le lien de vérification de son adresse (événement Registered),
     * puis le connecte.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        Validator::validate(
            data: $request->all(),
            rules: [
                'last_name' => ['required', 'string', 'max:30'],
                'first_name' => ['required', 'string', 'max:30'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:100', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ],
            attributes: [
                'last_name' => 'Nom',
                'first_name' => 'Prénom',
                'email' => 'E-mail',
                'password' => 'Mot de passe',
            ],
        );

        $user = User::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('portfolio.index');
    }
}
