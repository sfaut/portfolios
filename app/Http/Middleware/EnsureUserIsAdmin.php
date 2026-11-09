<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route à l'administrateur (`users.is_admin`), alias `admin`.
 *
 * Tout autre utilisateur reçoit la 404 d'une adresse inconnue : rien ne révèle la zone
 * d'administration. Placé juste après `auth`, il passe avant `verified` et `password.confirm`, et
 * pose sur toutes les réponses de l'administrateur, redirections comprises, des en-têtes qui
 * interdisent la mise en cache et l'indexation.
 */
class EnsureUserIsAdmin
{
    /**
     * En-têtes posés sur les réponses de la zone d'administration.
     *
     * @var array<string, string>
     */
    public const array HEADERS = [
        'Cache-Control' => 'no-store, private',
        'X-Robots-Tag' => 'noindex, nofollow',
    ];

    /**
     * Répond 404 à qui n'est pas administrateur, sinon passe la requête et marque la réponse.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin === true, Response::HTTP_NOT_FOUND);

        $response = $next($request);
        $response->headers->add(self::HEADERS);

        return $response;
    }
}
