<?php

namespace App\Support;

use App\Enums\AxisColor;
use App\Models\Portfolio;
use App\View\Components\MonthPicker;
use Carbon\CarbonImmutable;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Ventes d'un portfolio sur une période de mois, pour son tableau de bord : total comparé à la même
 * période un an plus tôt, ventes cumulées jour par jour, ventes par mois et répartition analytique.
 *
 * La période va du premier jour du mois de début au dernier jour du mois de fin, et s'arrête à la date
 * du calcul quand elle la dépasse (période en cours) : les ventes datées du futur n'y entrent pas, et la
 * période de comparaison s'arrête au même jour, un an plus tôt. Les ventes suivent la convention de
 * PortfolioSales (montant négatif, en valeur absolue, comptes archivés compris, par date effective).
 */
final readonly class SalesDashboard
{
    /**
     * Premier jour de la période.
     */
    public CarbonImmutable $from;

    /**
     * Premier jour qui suit le mois de fin : borne exclue de la période affichée.
     */
    public CarbonImmutable $to;

    /**
     * Premier jour exclu des ventes : $to, ou le lendemain de la date du calcul s'il vient avant.
     */
    public CarbonImmutable $until;

    /**
     * Ventes du portfolio.
     */
    private PortfolioSales $sales;

    /**
     * Retient la période et la date du calcul, et en déduit les bornes.
     *
     * @param  Portfolio  $portfolio  Portfolio dont les ventes sont lues.
     * @param  string  $start  Mois de début, compris (« 2026-01 »).
     * @param  string  $end  Mois de fin, compris (« 2026-11 »), égal ou postérieur au début.
     * @param  CarbonImmutable  $today  Date du calcul.
     */
    public function __construct(
        private Portfolio $portfolio,
        public string $start,
        public string $end,
        CarbonImmutable $today,
    ) {
        $this->sales = new PortfolioSales($portfolio);
        $this->from = CarbonImmutable::createFromFormat('!Y-m', $start);
        $this->to = CarbonImmutable::createFromFormat('!Y-m', $end)->addMonthNoOverflow();
        $this->until = max($this->from, min($this->to, $today->startOfDay()->addDay()));
    }

    /**
     * Total des ventes de la période, comparé à la même période un an plus tôt.
     */
    public function comparison(): SalesComparison
    {
        $previous_year = $this->from->year - 1;
        $reference = $this->from->year === $this->to->subDay()->year
            ? "la même période de {$previous_year}"
            : 'la même période un an plus tôt';

        return new SalesComparison(
            label: $this->periodLabel(),
            amount: $this->sales->total($this->from, $this->until) ?? 0.0,
            previous: $this->sales->total($this->from->subYearNoOverflow(), $this->until->subYearNoOverflow()),
            reference: $this->until < $this->to ? "{$reference}, à date" : $reference,
        );
    }

    /**
     * Période en toutes lettres : « en novembre 2026 », « de janvier à novembre 2026 »,
     * « de novembre 2025 à février 2026 ».
     */
    public function periodLabel(): string
    {
        $last = $this->to->subDay();
        $end_label = Str::lower(MonthPicker::monthLabel($this->end));

        return match (true) {
            $this->start === $this->end => "en {$end_label}",
            $this->from->year === $last->year => SalesFigures::ofMonth($this->from)." à {$end_label}",
            default => SalesFigures::ofMonth($this->from)." {$this->from->year} à {$end_label}",
        };
    }

    /**
     * Année ou années de la période, reculées du nombre d'années donné : « 2026 », « 2025-2026 ».
     *
     * @param  int  $years_back  Nombre d'années à reculer (1 pour la période de comparaison).
     */
    public function yearsLabel(int $years_back = 0): string
    {
        $first = $this->from->year - $years_back;
        $last = $this->to->subDay()->year - $years_back;

        return $first === $last ? (string) $first : "{$first}-{$last}";
    }

    /**
     * Nombre de jours de la période affichée, du premier jour du mois de début au dernier du mois de
     * fin.
     */
    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to);
    }

    /**
     * Ventes cumulées de la période et de la même période un an plus tôt, en paliers : un point par
     * jour de vente, situé par son nombre de jours depuis le début de sa période, et le jour où la
     * courbe s'arrête.
     *
     * @return array{current: array{points: list<array{0: int, 1: float}>, end: int}, previous: array{points: list<array{0: int, 1: float}>, end: int}}
     */
    public function cumulative(): array
    {
        return [
            'current' => $this->cumulativeBetween($this->from, $this->until),
            'previous' => $this->cumulativeBetween($this->from->subYearNoOverflow(), $this->until->subYearNoOverflow()),
        ];
    }

    /**
     * Ventes de chaque mois de la période, mois sans vente compris : total, nombre de ventes,
     * paiements, cumul depuis le début de la période, et ventes du même mois un an plus tôt, arrêtées
     * au même jour pour le mois en cours. Le libellé court est l'initiale du mois, l'année n'est
     * donnée qu'en janvier et au premier mois.
     *
     * @return list<array{month: string, label: string, initial: string, year: string, sales: float, count: int, payments: float, cumulative: float, previous: float}>
     */
    public function monthly(): array
    {
        $rows = $this->sales->operations($this->from, $this->until)
            ->toBase()
            ->selectRaw("DATE_FORMAT(delivery_at, '%Y-%m') AS month")
            ->selectRaw('-SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) AS sales')
            ->selectRaw('SUM(amount < 0) AS sale_count')
            ->selectRaw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS payments')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $previous = $this->sales->operations($this->from->subYearNoOverflow(), $this->until->subYearNoOverflow())
            ->toBase()
            ->selectRaw("DATE_FORMAT(DATE_ADD(delivery_at, INTERVAL 1 YEAR), '%Y-%m') AS month")
            ->selectRaw('-SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) AS sales')
            ->groupBy('month')
            ->pluck('sales', 'month');

        $cumulative = 0.0;
        $months = [];

        for ($month = $this->from; $month < $this->to; $month = $month->addMonthNoOverflow()) {
            $key = $month->format('Y-m');
            $row = $rows->get($key);
            $sales = (float) ($row->sales ?? 0);
            $cumulative += $sales;

            $months[] = [
                'month' => $key,
                'label' => MonthPicker::monthLabel($key),
                'initial' => Str::upper(mb_substr($month->translatedFormat('F'), 0, 1)),
                'year' => $month->month === 1 || $month == $this->from ? (string) $month->year : '',
                'sales' => $sales,
                'count' => (int) ($row->sale_count ?? 0),
                'payments' => (float) ($row->payments ?? 0),
                'cumulative' => round($cumulative, 2),
                'previous' => (float) ($previous->get($key) ?? 0),
            ];
        }

        return $months;
    }

    /**
     * Ventes de la période réparties par axe analytique, du plus gros montant au plus petit, puis les
     * ventes sans répartition (« Non réparti »), en dernier.
     *
     * La part d'une vente revenant à un axe s'arrondit au centime, la moitié s'éloignant de zéro, comme
     * AnalyticLine::amountShare(), en centimes et centièmes de pourcentage entiers. Un axe archivé
     * garde ses ventes, marqué comme tel. La part de chaque ligne est rapportée au total des ventes
     * (0 à 1), et donnée en pourcentage entier (« 58 % »). Chaque axe porte son icône et sa couleur,
     * « Non réparti » un cercle pointillé gris.
     *
     * @return list<array{name: string, icon: string, color: string, archived: bool, unallocated: bool, amount: float, share: float, percent: string}>
     */
    public function byAnalyticAxis(): array
    {
        $axes = $this->sales->operations($this->from, $this->until)
            ->where('operations.amount', '<', 0)
            ->join('analytic_lines', 'analytic_lines.operation_id', '=', 'operations.id')
            ->join('analytic_axes', 'analytic_axes.id', '=', 'analytic_lines.analytic_axis_id')
            ->toBase()
            ->select('analytic_axes.name', 'analytic_axes.icon', 'analytic_axes.color')
            ->selectRaw('analytic_axes.deleted_at IS NOT NULL AS archived')
            ->selectRaw('SUM((ABS(operations.amount) * 100 * analytic_lines.percentage * 100 + 5000) DIV 10000) AS cents')
            ->groupBy('analytic_axes.id', 'analytic_axes.name', 'analytic_axes.icon', 'analytic_axes.color', 'analytic_axes.deleted_at')
            ->orderByDesc('cents')
            ->orderBy('analytic_axes.name')
            ->get()
            ->map(fn (object $axis): array => [
                'name' => $axis->name,
                'icon' => $axis->icon,
                'color' => $axis->color,
                'archived' => (bool) $axis->archived,
                'unallocated' => false,
                'amount' => round(((int) $axis->cents) / 100, 2),
            ]);

        $unallocated = (float) $this->sales->operations($this->from, $this->until)
            ->where('operations.amount', '<', 0)
            ->whereDoesntHave('analyticLines')
            ->toBase()
            ->selectRaw('-COALESCE(SUM(operations.amount), 0) AS sales')
            ->value('sales');

        if ($unallocated > 0) {
            $axes->push(['name' => 'Non réparti', 'icon' => 'circle-dashed', 'color' => AxisColor::Gray->value, 'archived' => false, 'unallocated' => true, 'amount' => $unallocated]);
        }

        $total = $this->comparison()->amount;

        return $axes
            ->map(fn (array $axis): array => [
                ...$axis,
                'share' => $total > 0 ? $axis['amount'] / $total : 0.0,
                'percent' => Number::format($total > 0 ? round($axis['amount'] / $total * 100) : 0, locale: 'fr')."\u{202F}%",
            ])
            ->values()
            ->all();
    }

    /**
     * Ventes cumulées entre deux dates, en paliers par jour de vente.
     *
     * @param  CarbonImmutable  $from  Premier jour compris, origine des jours.
     * @param  CarbonImmutable  $until  Premier jour exclu, où la courbe s'arrête.
     * @return array{points: list<array{0: int, 1: float}>, end: int}
     */
    private function cumulativeBetween(CarbonImmutable $from, CarbonImmutable $until): array
    {
        $cumulative = 0.0;

        $points = $this->sales->operations($from, $until)
            ->where('amount', '<', 0)
            ->toBase()
            ->selectRaw('delivery_at AS day, -SUM(amount) AS sales')
            ->groupBy('delivery_at')
            ->orderBy('delivery_at')
            ->get()
            ->map(function (object $row) use ($from, &$cumulative): array {
                $cumulative += (float) $row->sales;

                return [(int) $from->diffInDays(CarbonImmutable::parse($row->day)), round($cumulative, 2)];
            })
            ->all();

        return ['points' => $points, 'end' => (int) $from->diffInDays($until)];
    }
}
