<?php

namespace App\Support;

use App\Models\Portfolio;
use Carbon\CarbonImmutable;

/**
 * Chiffres clefs d'un portfolio pour son tableau de bord, calculés à une date donnée : comptes
 * actifs, ventes du mois en cours, du mois précédent et des douze derniers mois (chacune comparée à
 * la même période un an plus tôt), et reste à encaisser.
 *
 * La date est passée au constructeur, pour que l'appelant (et les tests) la fixent. Les ventes
 * suivent la convention de PortfolioSales (montant négatif, en valeur absolue, comptes archivés
 * compris, par date effective).
 */
final readonly class SalesFigures
{
    /**
     * Ventes du portfolio.
     */
    private PortfolioSales $sales;

    /**
     * Retient le portfolio et la date, ramenée au début du jour.
     *
     * @param  Portfolio  $portfolio  Portfolio dont les chiffres sont calculés.
     * @param  CarbonImmutable  $today  Date du calcul.
     */
    public function __construct(
        private Portfolio $portfolio,
        private CarbonImmutable $today,
    ) {
        $this->sales = new PortfolioSales($portfolio);
    }

    /**
     * Nombre de comptes actifs (non archivés).
     */
    public function activeAccountCount(): int
    {
        return $this->portfolio->accounts()->count();
    }

    /**
     * Nombre de comptes archivés.
     */
    public function archivedAccountCount(): int
    {
        return $this->portfolio->accounts()->onlyTrashed()->count();
    }

    /**
     * Ventes du mois en cours, du 1er à la date du calcul comprise, comparées aux mêmes jours un an
     * plus tôt.
     */
    public function currentMonth(): SalesComparison
    {
        $from = $this->today->startOfDay()->startOfMonth();
        $until = $this->today->startOfDay()->addDay();

        return $this->compare(
            label: 'ventes '.self::ofMonth($from),
            from: $from,
            until: $until,
            reference: "{$this->monthName($from)} ".($from->year - 1).' à date',
        );
    }

    /**
     * Ventes du mois précédent, mois complet, comparées au même mois un an plus tôt.
     */
    public function previousMonth(): SalesComparison
    {
        $until = $this->today->startOfDay()->startOfMonth();
        $from = $until->subMonthNoOverflow();

        return $this->compare(
            label: 'ventes '.self::ofMonth($from),
            from: $from,
            until: $until,
            reference: "{$this->monthName($from)} ".($from->year - 1),
        );
    }

    /**
     * Ventes des douze mois complets qui précèdent le mois en cours, comparées aux douze mois
     * d'avant.
     */
    public function lastTwelveMonths(): SalesComparison
    {
        $until = $this->today->startOfDay()->startOfMonth();
        $from = $until->subMonthsNoOverflow(12);

        return new SalesComparison(
            label: 'ventes des 12 derniers mois',
            amount: $this->sales->total($from, $until) ?? 0.0,
            previous: $this->sales->total($from->subMonthsNoOverflow(12), $from),
            reference: 'les 12 mois précédents',
        );
    }

    /**
     * Reste à encaisser : ventes non encore payées, soit la somme des soldes négatifs des comptes
     * (archivés compris), en valeur absolue, avec le nombre de ces comptes. Un solde positif (paiement
     * d'avance) n'entre pas dans le calcul.
     *
     * @return array{amount: float, accounts: int}
     */
    public function outstanding(): array
    {
        $outstanding = $this->portfolio->accountsReport()
            ->withTrashed()
            ->where('operation_balance', '<', 0)
            ->toBase()
            ->selectRaw('-COALESCE(SUM(operation_balance), 0) AS amount, COUNT(*) AS accounts')
            ->first();

        return [
            'amount' => (float) $outstanding->amount,
            'accounts' => (int) $outstanding->accounts,
        ];
    }

    /**
     * Complément « de novembre », « d'octobre » : le nom du mois, avec élision devant une voyelle.
     *
     * @param  CarbonImmutable  $month  Un jour du mois.
     */
    public static function ofMonth(CarbonImmutable $month): string
    {
        $name = $month->translatedFormat('F');

        return preg_match('/^[aeiouy]/u', $name) === 1 ? "d'{$name}" : "de {$name}";
    }

    /**
     * Ventes d'une période, comparées à la même période un an plus tôt.
     *
     * @param  string  $label  Libellé de la période.
     * @param  CarbonImmutable  $from  Premier jour compris.
     * @param  CarbonImmutable  $until  Premier jour exclu.
     * @param  string  $reference  Période de référence, à écrire après « sur ».
     */
    private function compare(string $label, CarbonImmutable $from, CarbonImmutable $until, string $reference): SalesComparison
    {
        return new SalesComparison(
            label: $label,
            amount: $this->sales->total($from, $until) ?? 0.0,
            previous: $this->sales->total($from->subYearNoOverflow(), $until->subYearNoOverflow()),
            reference: $reference,
        );
    }

    /**
     * Nom du mois, en minuscules : « novembre ».
     *
     * @param  CarbonImmutable  $month  Un jour du mois.
     */
    private function monthName(CarbonImmutable $month): string
    {
        return $month->translatedFormat('F');
    }
}
