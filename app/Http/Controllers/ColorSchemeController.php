<?php

namespace App\Http\Controllers;

use App\Enums\ColorScheme;
use App\Http\Requests\ColorSchemeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Enregistre le thème de l'utilisateur connecté.
 *
 * L'interrupteur de la barre l'envoie par script, sans quitter la page : la réponse est alors vide
 * (204). Le formulaire de la page Préférences, sans script, revient à sa page avec un message.
 */
class ColorSchemeController extends Controller
{
    /**
     * Enregistre le thème choisi.
     */
    public function __invoke(ColorSchemeRequest $request): Response|RedirectResponse
    {
        $request->user()->update([
            'color_scheme' => ColorScheme::from($request->validated('color_scheme')),
        ]);

        return $request->expectsJson()
            ? response()->noContent()
            : back()->with('status', 'color-scheme-updated');
    }
}
