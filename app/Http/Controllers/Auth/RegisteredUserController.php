<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationAttempted;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Message de la page de connexion après une inscription, le même que l'adresse ait un compte ou non.
     */
    public const string REGISTRATION_STATUS = 'Un e-mail vient d\'être envoyé à l\'adresse indiquée pour terminer l\'inscription.';

    /**
     * Affiche le formulaire d'inscription.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Inscrit une adresse nouvelle, sans rien révéler d'une adresse qui a déjà un compte.
     *
     * Les deux cas reçoivent la même réponse, sans connexion : un renvoi vers la page de connexion qui
     * annonce un e-mail. À une adresse nouvelle, l'utilisateur est créé et reçoit le lien de vérification
     * (événement Registered) ; il se connectera en le suivant. À une adresse déjà inscrite, rien ne
     * change et son titulaire est prévenu (RegistrationAttempted). Le mot de passe est haché dans les
     * deux cas, pour que le temps de réponse ne les distingue pas.
     *
     * Seuls les champs validés sont enregistrés : le formulaire ne peut pas donner `is_admin`.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = Validator::validate(
            data: $request->all(),
            rules: [
                'last_name' => ['required', 'string', 'max:30'],
                'first_name' => ['required', 'string', 'max:30'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:100'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ],
            attributes: [
                'last_name' => 'Nom',
                'first_name' => 'Prénom',
                'email' => 'E-mail',
                'password' => 'Mot de passe',
            ],
        );

        $password_hash = Hash::make($validated['password']);

        $existing_user = User::query()->where('email', $validated['email'])->first();

        if ($existing_user === null) {
            $user = User::create([
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'email' => $validated['email'],
                'password' => $password_hash,
            ]);

            event(new Registered($user));
        } else {
            $existing_user->notify(new RegistrationAttempted);
        }

        return to_route('login')->with('status', self::REGISTRATION_STATUS);
    }
}
