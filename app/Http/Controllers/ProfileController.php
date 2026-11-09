<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileAvatarRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Support\AvatarImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Profil de l'utilisateur connecté, en quatre pages derrière la confirmation du mot de passe
 * (middleware `password.confirm`) : informations, préférences (thème, ColorSchemeController), mot de
 * passe (PasswordController), données et compte (export, suppression).
 */
class ProfileController extends Controller
{
    /**
     * Affiche la première page du profil : sa photo, puis ses informations.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'avatar_accept' => implode(',', array_keys(AvatarImage::supportedTypes())),
            'avatar_formats' => ProfileAvatarRequest::formats().', '.(ProfileAvatarRequest::MAX_KILOBYTES / 1024).' Mo au plus.',
        ]);
    }

    /**
     * Affiche les préférences : le thème.
     */
    public function preferences(Request $request): View
    {
        return view('profile.preferences', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Affiche le changement du mot de passe.
     */
    public function password(Request $request): View
    {
        return view('profile.password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Affiche les données et le compte : export des données, suppression du compte.
     */
    public function account(Request $request): View
    {
        return view('profile.account', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Enregistre le profil ; une nouvelle adresse perd sa vérification et reçoit un lien pour la retrouver.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        $email_changed = $user->isDirty('email');

        if ($email_changed) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($email_changed) {
            $user->sendEmailVerificationNotification();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Supprime le compte de l'utilisateur, après vérification de son mot de passe, puis le déconnecte.
     *
     * Les clefs étrangères en cascade suppriment avec lui ses portfolios, comptes, opérations et axes
     * analytiques, archivés compris. Le compte administrateur est refusé : la base garderait sinon ses
     * données sans administrateur.
     *
     * @throws ValidationException
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->is_admin) {
            throw ValidationException::withMessages([
                'password' => 'Le compte administrateur ne peut pas être supprimé.',
            ])->errorBag('userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
