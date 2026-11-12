<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileAvatarRequest;
use App\Models\User;
use App\Support\AvatarImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Photo de profil de l'utilisateur connecté : affichage, envoi et retrait.
 *
 * La photo, normalisée par App\Support\AvatarImage, est rangée à plat sur le disque privé des photos
 * (User::avatarDisk()), sous `avatars/{id}-{40 caractères aléatoires}.webp`. Elle ne se sert que par `show`, qui ne prend
 * aucun identifiant : chacun n'atteint que la sienne. Remplacement et retrait suppriment aussi les
 * orphelins de l'utilisateur (User::deleteAvatarFiles()).
 */
class ProfileAvatarController extends Controller
{
    /**
     * Sert la photo de l'utilisateur connecté, 404 sans photo.
     *
     * L'adresse porte le nom du fichier (`?v=`), qui change à chaque photo : la réponse se garde donc
     * un an dans le cache du navigateur, et seulement le sien.
     */
    public function show(Request $request): StreamedResponse
    {
        $path = $request->user()->avatar_path;

        $disk = User::avatarDisk();

        abort_if($path === '' || ! $disk->exists($path), 404);

        return $disk->response($path, 'photo.webp', [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }

    /**
     * Enregistre une nouvelle photo, normalisée, et supprime l'ancienne avec tout orphelin.
     *
     * @throws ValidationException
     */
    public function update(ProfileAvatarRequest $request): RedirectResponse
    {
        try {
            $webp = AvatarImage::normalize($request->file('avatar')->getRealPath());
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['avatar' => 'Cette image ne peut pas être lue.']);
        }

        $user = $request->user();
        $path = "avatars/{$user->id}-".Str::random(40).'.webp';

        // Un disque à `throw` faux, comme celui d'un bucket Laravel Cloud, signale l'échec par false.
        if (! User::avatarDisk()->put($path, $webp)) {
            throw ValidationException::withMessages(['avatar' => "La photo n'a pas pu être enregistrée. Réessayez plus tard."]);
        }

        $user->forceFill(['avatar_path' => $path])->save();
        $user->deleteAvatarFiles(except: $path);

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Retire la photo : l'avatar reprend les initiales.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->deleteAvatarFiles();

        if ($user->avatar_path !== '') {
            $user->forceFill(['avatar_path' => ''])->save();
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-deleted');
    }
}
