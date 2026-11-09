<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * Plage de dates préréglée, proposée par un sélecteur de date en mode range (<x-date-picker>) :
 * un clic la choisit, sans passer par les calendriers.
 *
 * Chaque plage se calcule sur le serveur, par rapport au jour courant.
 */
enum DatePreset: string
{
    case Today = 'today';
    case ThisMonth = 'this-month';
    case LastMonth = 'last-month';
    case ThisYear = 'this-year';
    case LastTwelveMonths = 'last-twelve-months';

    /**
     * Libellé du préréglage.
     */
    public function label(): string
    {
        return match ($this) {
            self::Today => 'Aujourd\'hui',
            self::ThisMonth => 'Ce mois',
            self::LastMonth => 'Mois précédent',
            self::ThisYear => 'Cette année',
            self::LastTwelveMonths => '12 derniers mois',
        };
    }

    /**
     * Plage du préréglage, bornes comprises : le mois et l'année en cours vont jusqu'à leur dernier
     * jour, et les 12 derniers mois finissent au jour courant.
     *
     * @param  CarbonImmutable  $today  Jour courant.
     * @return array{start: string, end: string} Jours « 2026-11-09 ».
     */
    public function range(CarbonImmutable $today): array
    {
        [$start, $end] = match ($this) {
            self::Today => [$today, $today],
            self::ThisMonth => [$today->startOfMonth(), $today->endOfMonth()],
            self::LastMonth => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            self::ThisYear => [$today->startOfYear(), $today->endOfYear()],
            self::LastTwelveMonths => [$today->subMonthsNoOverflow(12)->addDay(), $today],
        };

        return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
    }
}
