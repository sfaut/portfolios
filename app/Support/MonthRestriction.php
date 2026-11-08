<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Restriction des mois qu'un sélecteur de mois accepte : saisie libre, ou restreinte par des bornes,
 * une liste blanche et une liste noire.
 *
 * Un mois s'écrit « 2026-11 ». Un mois est choisissable s'il est :
 * - dans les bornes, min ≤ mois ≤ max, chacune facultative ;
 * - dans une entrée de la liste blanche (allow), quand elle est donnée ;
 * - dans aucune entrée de la liste noire (deny).
 *
 * Une entrée de liste est un mois (« 2026-11 ») ou une plage fermée en intervalle ISO 8601
 * (« 2025-01/2025-06 », bornes comprises) ; une plage écrite à l'envers est remise dans l'ordre. Sans
 * min ou max, la borne se déduit de la liste blanche (début de sa première plage, fin de sa dernière).
 *
 * La restriction est la seule source de vérité : le serveur la construit lui-même, ne la lit jamais
 * depuis la requête, la passe au composant <x-month-picker> pour l'affichage (toArray()) et contrôle
 * avec elle la valeur reçue (App\Rules\MonthSelection). resources/js/month-picker-rules.js en reproduit
 * la règle pour griser les mois dans le navigateur, sans rien valider.
 */
final readonly class MonthRestriction
{
    /**
     * Forme d'un mois : année sur quatre chiffres, tiret, mois sur deux chiffres ; le modificateur D
     * refuse le saut de ligne final que « $ » accepte sinon.
     */
    public const string MONTH_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/D';

    /**
     * Premier mois choisissable, vide sans borne.
     */
    public string $min;

    /**
     * Dernier mois choisissable, vide sans borne.
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
     * @param  string  $min  Premier mois choisissable, vide sans borne (déduit de la liste blanche à défaut).
     * @param  string  $max  Dernier mois choisissable, vide sans borne (déduit de la liste blanche à défaut).
     * @param  list<string>|null  $allow  Liste blanche de mois et de plages, ou null pour ne pas restreindre.
     * @param  list<string>  $deny  Liste noire de mois et de plages.
     *
     * @throws InvalidArgumentException Si une borne ou une entrée n'est pas un mois ou une plage de mois.
     */
    public function __construct(
        string $min = '',
        string $max = '',
        ?array $allow = null,
        array $deny = [],
    ) {
        foreach (['min' => $min, 'max' => $max] as $bound => $month) {
            if ($month !== '' && ! self::isMonth($month)) {
                throw new InvalidArgumentException("La borne {$bound} « {$month} » n'est pas un mois AAAA-MM.");
            }
        }

        $this->allow = $allow === null ? null : self::ranges($allow);
        $this->deny = self::ranges($deny);

        $this->min = $min !== '' ? $min : ($this->allow[0][0] ?? '');
        $this->max = $max !== '' ? $max : ($this->allow === null || $this->allow === [] ? '' : max(array_column($this->allow, 1)));
    }

    /**
     * Si la valeur est un mois de la forme « 2026-11 ».
     *
     * @param  mixed  $value  Valeur à vérifier.
     */
    public static function isMonth(mixed $value): bool
    {
        return is_string($value) && preg_match(self::MONTH_PATTERN, $value) === 1;
    }

    /**
     * Si la valeur est un mois choisissable : de la forme « 2026-11 », dans les bornes, dans la liste
     * blanche quand elle est donnée, et hors de la liste noire.
     *
     * Les mois « 2026-11 » se comparent comme des textes, dans l'ordre des dates.
     *
     * @param  mixed  $month  Valeur à vérifier, de n'importe quel type (valeur reçue d'une requête).
     */
    public function isChoosable(mixed $month): bool
    {
        return self::isMonth($month)
            && ($this->min === '' || $month >= $this->min)
            && ($this->max === '' || $month <= $this->max)
            && ($this->allow === null || self::covers($this->allow, $month))
            && ! self::covers($this->deny, $month);
    }

    /**
     * Restriction sous la forme transmise au composant Alpine monthPicker.
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
     * @param  list<string>  $entries  Mois « 2026-11 » et plages « 2025-01/2025-06 ».
     * @return list<array{0: string, 1: string}>
     *
     * @throws InvalidArgumentException Si une entrée n'est pas un mois ou une plage de mois.
     */
    private static function ranges(array $entries): array
    {
        $ranges = array_map(function (mixed $entry): array {
            $bounds = is_string($entry) ? explode('/', $entry) : [];

            if (! in_array(count($bounds), [1, 2], true) || ! self::isMonth($bounds[0]) || ! self::isMonth($bounds[count($bounds) - 1])) {
                $text = is_string($entry) ? $entry : get_debug_type($entry);

                throw new InvalidArgumentException("L'entrée « {$text} » n'est ni un mois AAAA-MM ni une plage AAAA-MM/AAAA-MM.");
            }

            $start = $bounds[0];
            $end = $bounds[count($bounds) - 1];

            return $start <= $end ? [$start, $end] : [$end, $start];
        }, array_values($entries));

        usort($ranges, fn (array $a, array $b): int => $a <=> $b);

        return $ranges;
    }

    /**
     * Si une des plages contient le mois.
     *
     * @param  list<array{0: string, 1: string}>  $ranges  Plages [début, fin].
     * @param  string  $month  Mois « 2026-11 ».
     */
    private static function covers(array $ranges, string $month): bool
    {
        foreach ($ranges as [$start, $end]) {
            if ($month >= $start && $month <= $end) {
                return true;
            }
        }

        return false;
    }
}
