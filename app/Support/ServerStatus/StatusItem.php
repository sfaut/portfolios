<?php

namespace App\Support\ServerStatus;

/**
 * Ligne du relevé de l'état du serveur : un libellé, sa valeur mise en forme, son niveau et, pour une
 * mesure à remplir (mémoire, disque…), son taux de remplissage.
 */
final readonly class StatusItem
{
    /**
     * Niveau d'une valeur normale.
     */
    public const string OK = 'ok';

    /**
     * Niveau d'une valeur à surveiller.
     */
    public const string WARNING = 'warning';

    /**
     * Niveau d'une anomalie.
     */
    public const string DANGER = 'danger';

    /**
     * Niveaux, du meilleur au pire.
     *
     * @var list<string>
     */
    public const array LEVELS = [self::OK, self::WARNING, self::DANGER];

    /**
     * @param  string  $label  Libellé (« Version de PHP »).
     * @param  string  $value  Valeur mise en forme, ou « Non disponible ».
     * @param  string  $level  Niveau : ok, warning ou danger.
     * @param  string  $alert  Message de l'alerte quand le niveau n'est pas ok, '' sinon.
     * @param  float|null  $meter  Taux de remplissage de 0 à 1, null hors d'une mesure à remplir.
     */
    public function __construct(
        public string $label,
        public string $value,
        public string $level = self::OK,
        public string $alert = '',
        public ?float $meter = null,
    ) {
        //
    }

    /**
     * Pire de plusieurs niveaux, ok sans niveau.
     *
     * @param  iterable<string>  $levels  Niveaux comparés.
     */
    public static function worst(iterable $levels): string
    {
        $worst = 0;

        foreach ($levels as $level) {
            $worst = max($worst, (int) array_search($level, self::LEVELS, true));
        }

        return self::LEVELS[$worst];
    }
}
