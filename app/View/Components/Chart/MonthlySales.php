<?php

namespace App\View\Components\Chart;

use App\Support\ChartScale;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Ventes par mois en barres, avec les ventes du même mois un an plus tôt en barres fantômes, et le
 * cumul des ventes de la période en courbe, sur un axe secondaire, sans bibliothèque.
 *
 * Les deux axes ont le même nombre de pas (ChartScale), donc les mêmes lignes de repère : à gauche les
 * ventes du mois (barres et fantômes), à droite le cumul. Les barres portent un léger dégradé vertical,
 * du plus clair en bas au plus foncé en haut, ancré sur la hauteur du graphe et non sur celle de la
 * barre : sa taille est agrandie à celle du graphe et calée en bas, si bien que le haut d'une barre
 * fonce d'autant plus qu'elle monte. La courbe part de zéro, au bord gauche, et passe par le
 * cumul de chaque mois, au-dessus du centre de sa barre, elle-même centrée dans la colonne du mois
 * (le fantôme s'accroche à sa gauche) : un SVG étiré sur sa boîte (preserveAspectRatio="none", traits
 * d'épaisseur constante par vector-effect), ses points en HTML pour rester ronds.
 */
class MonthlySales extends Component
{
    /**
     * Nombre de pas des deux axes.
     */
    public const int STEPS = 4;

    /**
     * Graduations partagées : position (%), valeur de l'axe gauche et de l'axe droit.
     *
     * @var list<array{percent: float, left: string, right: string}>
     */
    public array $ticks;

    /**
     * Colonnes, une par mois : données du mois (SalesDashboard::monthly()), hauteurs de la barre et du
     * fantôme (%), hauteur du dégradé rapportée à la barre (%, celle du graphe) et position du point de
     * la courbe (%).
     *
     * @var list<array<string, mixed>>
     */
    public array $columns;

    /**
     * Points de la courbe du cumul, dans une boîte de 100 × 100 (y vers le bas), depuis zéro.
     */
    public string $line;

    /**
     * Prépare les échelles et la géométrie du graphe.
     *
     * @param  list<array{month: string, label: string, initial: string, year: string, sales: float, count: int, payments: float, cumulative: float, previous: float}>  $months  Ventes de chaque mois de la période (SalesDashboard::monthly()).
     * @param  string  $years  Année ou années de la période, pour la légende (« 2026 »).
     * @param  string  $previousYears  Année ou années un an plus tôt (« 2025 »).
     * @param  string  $label  Description accessible du graphe.
     */
    public function __construct(
        public array $months,
        public string $years,
        public string $previousYears,
        public string $label,
    ) {
        $left = new ChartScale(max([0.0, ...array_column($months, 'sales'), ...array_column($months, 'previous')]), self::STEPS);
        $right = new ChartScale(max([0.0, ...array_column($months, 'cumulative')]), self::STEPS);
        $count = max(1, count($months));

        $this->ticks = array_map(
            fn (array $left_tick, array $right_tick): array => [
                'percent' => $left_tick['percent'],
                'left' => ChartScale::format($left_tick['value']),
                'right' => ChartScale::format($right_tick['value']),
            ],
            $left->ticks(),
            $right->ticks(),
        );

        $this->columns = array_map(fn (int $index, array $month): array => [
            ...$month,
            'height' => $left->percent($month['sales']),
            'ghost' => $left->percent($month['previous']),
            'gradient' => $month['sales'] > 0 ? round(100 * 100 / $left->percent($month['sales']), 2) : 100.0,
            'x' => round(($index + 0.5) / $count * 100, 4),
            'y' => $right->percent($month['cumulative']),
        ], array_keys($months), $months);

        $this->line = collect([['x' => 0.0, 'y' => 0.0], ...$this->columns])
            ->map(fn (array $point): string => $point['x'].','.round(100 - $point['y'], 4))
            ->implode(' ');
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.chart.monthly-sales');
    }
}
