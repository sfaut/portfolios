<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ServerStatus\ServerStatus;
use App\Support\ServerStatus\StatusItem;
use Illuminate\View\View;

/**
 * État du serveur, page de l'administrateur : relevé de l'application, de PHP, du système, de la base,
 * de la file et des données, ses alertes en tête.
 */
class ServerStatusController extends Controller
{
    /**
     * Type de <x-alert> du résumé, selon le niveau du relevé.
     *
     * @var array<string, string>
     */
    public const array ALERT_TYPES = [
        StatusItem::OK => 'success',
        StatusItem::WARNING => 'warning',
        StatusItem::DANGER => 'danger',
    ];

    /**
     * Affiche le relevé, pris à l'instant de la requête.
     *
     * @param  ServerStatus  $status  Relevé, fourni par le conteneur.
     */
    public function __invoke(ServerStatus $status): View
    {
        $alerts = $status->alerts();
        $dangers = count(array_filter($alerts, fn (array $alert): bool => $alert['item']->level === StatusItem::DANGER));

        return view('admin.server', [
            'taken_at' => $status->takenAt->translatedFormat('j F Y à H:i:s'),
            'sections' => $status->sections(),
            'alerts' => $alerts,
            'alert_type' => self::ALERT_TYPES[$status->level()],
            'alert_title' => match (true) {
                $alerts === [] => 'Rien à signaler',
                $dangers > 0 => trans_choice('{1} Une anomalie|[2,*] :count anomalies', $dangers)
                    .(count($alerts) > $dangers ? ' '.trans_choice('{1} et un point à surveiller|[2,*] et :count points à surveiller', count($alerts) - $dangers) : ''),
                default => trans_choice('{1} Un point à surveiller|[2,*] :count points à surveiller', count($alerts)),
            },
        ]);
    }
}
