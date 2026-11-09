<?php

namespace App\View\Components\Chart;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use Illuminate\View\Component;

/**
 * Courbe en paliers de valeurs cumulées dans le temps (ventes cumulées), une ou plusieurs séries
 * superposées sur la même période, sans bibliothèque : un SVG étiré sur sa boîte, la valeur de fin de
 * chaque série écrite dans une marge à droite, et les mois sous la courbe.
 *
 * Le SVG a une boîte de VIEWBOX_WIDTH × VIEWBOX_HEIGHT unités, étirée à la taille de son conteneur
 * (preserveAspectRatio="none") ; ses traits gardent leur épaisseur (vector-effect). Les textes sont
 * en HTML, placés en pourcentage, pour ne pas être déformés. L'échelle part de zéro et monte un peu
 * au-dessus de la plus grande valeur.
 *
 * Une série donne ses points en jours depuis le début de la période, chacun avec le cumul atteint ce
 * jour-là, et le jour où elle s'arrête : la courbe part de zéro, monte d'un palier à chaque point, puis
 * reste à plat jusqu'au jour d'arrêt, d'où un filet pointillé la relie à son étiquette.
 */
class StepLine extends Component
{
    /**
     * Largeur de la boîte du SVG, en unités.
     */
    public const int VIEWBOX_WIDTH = 1000;

    /**
     * Hauteur de la boîte du SVG, en unités.
     */
    public const int VIEWBOX_HEIGHT = 300;

    /**
     * Écart minimal entre deux étiquettes de fin, en pourcentage de la hauteur.
     */
    public const float LABEL_GAP = 16.0;

    /**
     * Tracés des séries : points de la polyligne, classes du trait, pointillé.
     *
     * @var list<array{points: string, line: string, dashed: bool, leader: string}>
     */
    public array $polylines;

    /**
     * Étiquettes de fin, dans la marge : position verticale (%), montant, légende et classes.
     *
     * @var list<array{top: float, amount: string, caption: string, text: string}>
     */
    public array $labels;

    /**
     * Mois sous la courbe : position horizontale (%), mois (« janv. »), année (« 2026 », ou vide) et
     * mois secondaire, masqué sur un écran étroit.
     *
     * @var list<array{left: float, text: string, year: string, minor: bool}>
     */
    public array $ticks;

    /**
     * Calcule l'échelle, les tracés, les étiquettes de fin et les mois.
     *
     * @param  CarbonImmutable  $from  Premier jour de la période, origine des jours.
     * @param  int  $days  Nombre de jours de la période.
     * @param  list<array{points: list<array{0: int, 1: float}>, end: int, caption: string, line: string, text: string, dashed?: bool}>  $series
     *                                                                                                                                            Séries, de la première plan à l'arrière-plan : points (jour, cumul), jour d'arrêt, légende de
     *                                                                                                                                            l'étiquette (« 2026 »), classes du trait (stroke-*) et du texte (text-*), pointillé.
     * @param  string  $label  Description accessible de la courbe.
     */
    public function __construct(
        public CarbonImmutable $from,
        public int $days,
        public array $series,
        public string $label,
    ) {
        $days = max(1, $days);
        $highest = max([0.0, ...array_map(fn (array $serie): float => (float) (end($serie['points'])[1] ?? 0), $series)]);
        $top = $highest > 0 ? $highest * 1.08 : 1.0;

        $x = fn (int $day): float => round(min($day, $days) / $days * self::VIEWBOX_WIDTH, 2);
        $y = fn (float $value): float => round(self::VIEWBOX_HEIGHT - $value / $top * self::VIEWBOX_HEIGHT, 2);

        $this->polylines = [];
        $labels = [];

        foreach (array_reverse($series) as $serie) {
            $last_y = $y(0.0);
            $points = ["0,{$last_y}"];

            foreach ($serie['points'] as [$day, $value]) {
                $points[] = "{$x($day)},{$last_y}";
                $last_y = $y($value);
                $points[] = "{$x($day)},{$last_y}";
            }

            $end_x = $x($serie['end']);
            $points[] = "{$end_x},{$last_y}";

            $this->polylines[] = [
                'points' => implode(' ', $points),
                'line' => $serie['line'],
                'dashed' => $serie['dashed'] ?? false,
                'leader' => $end_x < self::VIEWBOX_WIDTH ? "{$end_x},{$last_y} ".self::VIEWBOX_WIDTH.",{$last_y}" : '',
            ];

            $labels[] = [
                'top' => $last_y / self::VIEWBOX_HEIGHT * 100,
                'amount' => Number::amount((float) (end($serie['points'])[1] ?? 0), currency: 'EUR'),
                'caption' => $serie['caption'],
                'text' => $serie['text'],
            ];
        }

        $this->labels = self::spread(array_reverse($labels));
        $this->ticks = $this->monthTicks($days);
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.chart.step-line');
    }

    /**
     * Étiquettes écartées d'au moins LABEL_GAP, de haut en bas, sans sortir de la hauteur.
     *
     * @param  list<array{top: float, amount: string, caption: string, text: string}>  $labels  Étiquettes à leur hauteur exacte.
     * @return list<array{top: float, amount: string, caption: string, text: string}>
     */
    private static function spread(array $labels): array
    {
        $order = array_keys($labels);
        usort($order, fn (int $a, int $b): int => $labels[$a]['top'] <=> $labels[$b]['top']);

        $previous = null;

        foreach ($order as $index) {
            $top = max($labels[$index]['top'], self::LABEL_GAP / 2);

            if ($previous !== null && $top - $previous < self::LABEL_GAP) {
                $top = $previous + self::LABEL_GAP;
            }

            $labels[$index]['top'] = round(min($top, 100.0), 2);
            $previous = $top;
        }

        return $labels;
    }

    /**
     * Mois de la période sous la courbe : tous jusqu'à douze mois, un par trimestre jusqu'à trois ans,
     * puis janvier seul. L'année accompagne janvier et le premier mois. Sur un écran étroit, la vue
     * masque l'année et les mois secondaires (minor) : il reste un mois par trimestre jusqu'à un an,
     * puis janvier seul, et toujours le premier mois.
     *
     * @param  int  $days  Nombre de jours de la période, au moins un.
     * @return list<array{left: float, text: string, year: string, minor: bool}>
     */
    private function monthTicks(int $days): array
    {
        $end = $this->from->addDays($days);
        $count = (int) $this->from->diffInMonths($end);
        $step = match (true) {
            $count <= 12 => 1,
            $count <= 36 => 3,
            default => 12,
        };

        $ticks = [];

        for ($month = $this->from; $month < $end; $month = $month->addMonthNoOverflow()) {
            $is_first = $month == $this->from;

            if (! $is_first && (($month->month - 1) % $step !== 0 || $this->from->diffInMonths($month) < min($step, 2))) {
                continue;
            }

            $ticks[] = [
                'left' => round($this->from->diffInDays($month) / $days * 100, 2),
                'text' => $month->translatedFormat('M'),
                'year' => $month->month === 1 || $is_first ? (string) $month->year : '',
                'minor' => ! $is_first && match ($step) {
                    1 => ($month->month - 1) % 3 !== 0,
                    3 => $month->month !== 1,
                    default => false,
                },
            ];
        }

        return $ticks;
    }
}
