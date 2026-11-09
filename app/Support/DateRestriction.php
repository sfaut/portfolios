<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Restriction des jours qu'un sélecteur de date accepte : saisie libre, ou restreinte par des bornes,
 * une liste blanche et une liste noire.
 *
 * Un jour s'écrit « 2026-11-09 ». Un jour est choisissable s'il est :
 * - dans les bornes, min ≤ jour ≤ max, chacune facultative ;
 * - dans une entrée de la liste blanche (allow), quand elle est donnée ;
 * - dans aucune entrée de la liste noire (deny).
 *
 * Une entrée de liste est un jour (« 2026-11-09 ») ou une plage fermée en intervalle ISO 8601
 * (« 2026-01-01/2026-01-31 », bornes comprises) ; une plage écrite à l'envers est remise dans l'ordre.
 * Sans min ou max, la borne se déduit de la liste blanche (début de sa première plage, fin de sa
 * dernière).
 *
 * La restriction est la seule source de vérité : le serveur la construit lui-même, ne la lit jamais
 * depuis la requête, la passe au composant <x-date-picker> pour l'affichage (toArray()) et contrôle
 * avec elle la valeur reçue (App\Rules\DateSelection). resources/js/date-picker-rules.js en reproduit
 * la règle pour griser les jours dans le navigateur, sans rien valider.
 *
 * Elle suit App\Support\MonthRestriction, qui fait de même pour les mois.
 */
final readonly class DateRestriction
{
    /**
     * Forme d'un jour : année sur quatre chiffres, mois et jour sur deux chiffres, séparés par des
     * tirets ; le modificateur D refuse le saut de ligne final que « $ » accepte sinon.
     */
    public const string DATE_PATTERN = '/^(\d{4})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/D';

    /**
     * Premier jour choisissable, vide sans borne.
     */
    public string $min;

    /**
     * Dernier jour choisissable, vide sans borne.
     */
    public string $max;

    /**
     * Liste blanche en plages [début, fin] triées, ou null sans liste blanche (saisie libre).
     *
     * @var list<array{0: string, 1: string}>|null
     */
    public ?array $allow;

    /**
     * Liste noire en plages [début, fin] triées, vide sans liste noire.
     *
     * @var list<array{0: string, 1: string}>
     */
    public array $deny;

    /**
     * Normalise les listes en plages triées et déduit de la liste blanche les bornes absentes.
     *
     * @param  string  $min  Premier jour choisissable, vide sans borne (déduit de la liste blanche à défaut).
     * @param  string  $max  Dernier jour choisissable, vide sans borne (déduit de la liste blanche à défaut).
     * @param  list<string>|null  $allow  Liste blanche de jours et de plages, ou null pour ne pas restreindre.
     * @param  list<string>  $deny  Liste noire de jours et de plages.
     *
     * @throws InvalidArgumentException Si une borne ou une entrée n'est pas un jour ou une plage de jours.
     */
    public function __construct(
        string $min = '',
        string $max = '',
        ?array $allow = null,
        array $deny = [],
    ) {
        foreach (['min' => $min, 'max' => $max] as $bound => $day) {
            if ($day !== '' && ! self::isDate($day)) {
                throw new InvalidArgumentException("La borne {$bound} « {$day} » n'est pas un jour AAAA-MM-JJ.");
            }
        }

        $this->allow = $allow === null ? null : self::ranges($allow);
        $this->deny = self::ranges($deny);

        $this->min = $min !== '' ? $min : ($this->allow[0][0] ?? '');
        $this->max = $max !== '' ? $max : ($this->allow === null || $this->allow === [] ? '' : max(array_column($this->allow, 1)));
    }

    /**
     * Si la valeur est un jour réel de la forme « 2026-11-09 » (« 2026-02-30 » est refusé).
     *
     * @param  mixed  $value  Valeur à vérifier.
     */
    public static function isDate(mixed $value): bool
    {
        return is_string($value)
            && preg_match(self::DATE_PATTERN, $value, $parts) === 1
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    /**
     * Si la valeur est un jour choisissable : un jour réel, dans les bornes, dans la liste blanche
     * quand elle est donnée, et hors de la liste noire.
     *
     * Les jours « 2026-11-09 » se comparent comme des textes, dans l'ordre des dates.
     *
     * @param  mixed  $day  Valeur à vérifier, de n'importe quel type (valeur reçue d'une requête).
     */
    public function isChoosable(mixed $day): bool
    {
        return self::isDate($day)
            && ($this->min === '' || $day >= $this->min)
            && ($this->max === '' || $day <= $this->max)
            && ($this->allow === null || self::covers($this->allow, $day))
            && ! self::covers($this->deny, $day);
    }

    /**
     * Restriction sous la forme transmise au composant Alpine datePicker.
     *
     * @return array{min: string, max: string, allow: list<array{0: string, 1: string}>|null, deny: list<array{0: string, 1: string}>}
     */
    public function toArray(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
            'allow' => $this->allow,
            'deny' => $this->deny,
        ];
    }

    /**
     * Entrées d'une liste en plages [début, fin], triées par début.
     *
     * @param  list<string>  $entries  Jours « 2026-11-09 » et plages « 2026-01-01/2026-01-31 ».
     * @return list<array{0: string, 1: string}>
     *
     * @throws InvalidArgumentException Si une entrée n'est pas un jour ou une plage de jours.
     */
    private static function ranges(array $entries): array
    {
        $ranges = array_map(function (mixed $entry): array {
            $bounds = is_string($entry) ? explode('/', $entry) : [];

            if (! in_array(count($bounds), [1, 2], true) || ! self::isDate($bounds[0]) || ! self::isDate($bounds[count($bounds) - 1])) {
                $text = is_string($entry) ? $entry : get_debug_type($entry);

                throw new InvalidArgumentException("L'entrée « {$text} » n'est ni un jour AAAA-MM-JJ ni une plage AAAA-MM-JJ/AAAA-MM-JJ.");
            }

            $start = $bounds[0];
            $end = $bounds[count($bounds) - 1];

            return $start <= $end ? [$start, $end] : [$end, $start];
        }, array_values($entries));

        usort($ranges, fn (array $a, array $b): int => $a <=> $b);

        return $ranges;
    }

    /**
     * Si une des plages contient le jour.
     *
     * @param  list<array{0: string, 1: string}>  $ranges  Plages [début, fin].
     * @param  string  $day  Jour « 2026-11-09 ».
     */
    private static function covers(array $ranges, string $day): bool
    {
        foreach ($ranges as [$start, $end]) {
            if ($day >= $start && $day <= $end) {
                return true;
            }
        }

        return false;
    }
}
