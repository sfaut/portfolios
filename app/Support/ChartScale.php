<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Échelle d'un axe de graphe, de zéro à un maximum « rond » : un nombre fixe de pas égaux, chacun de
 * 1, 2, 2,5 ou 5 fois une puissance de dix, le plus petit qui couvre la valeur maximale.
 *
 * Deux axes de même nombre de pas partagent leurs lignes de repère, quelles que soient leurs valeurs.
 */
final readonly class ChartScale
{
    /**
     * Multiplicateurs d'une puissance de dix acceptés pour le pas, dans l'ordre croissant.
     *
     * @var list<float>
     */
    public const array NICE_STEPS = [1.0, 2.0, 2.5, 5.0, 10.0];

    /**
     * Pas entre deux graduations.
     */
    public float $step;

    /**
     * Valeur du haut de l'axe : le pas multiplié par le nombre de pas.
     */
    public float $max;

    /**
     * Calcule le pas et le haut de l'axe. Un maximum nul ou négatif donne un pas de 1.
     *
     * @param  float  $value  Plus grande valeur à représenter.
     * @param  int  $steps  Nombre de pas, au moins 1.
     */
    public function __construct(
        float $value,
        public int $steps = 4,
    ) {
        $this->step = $value <= 0 ? 1.0 : self::niceStep($value / max(1, $steps));
        $this->max = $this->step * max(1, $steps);
    }

    /**
     * Graduations, de zéro au haut de l'axe : valeur et position en pourcentage de la hauteur.
     *
     * @return list<array{value: float, percent: float}>
     */
    public function ticks(): array
    {
        return array_map(
            fn (int $index): array => ['value' => $this->step * $index, 'percent' => round($index / $this->steps * 100, 2)],
            range(0, $this->steps),
        );
    }

    /**
     * Position d'une valeur en pourcentage de la hauteur de l'axe, bornée à 0 et 100.
     */
    public function percent(float $value): float
    {
        return round(min(100.0, max(0.0, $value / $this->max * 100)), 2);
    }

    /**
     * Graduation écrite sans décimale, en français : « 6 000 ».
     */
    public static function format(float $value): string
    {
        return Number::format($value, precision: 0, locale: 'fr');
    }

    /**
     * Plus petit pas rond (1, 2, 2,5 ou 5 × 10ⁿ) égal ou supérieur au pas brut.
     *
     * @param  float  $raw  Pas brut, positif.
     */
    private static function niceStep(float $raw): float
    {
        $magnitude = 10 ** floor(log10($raw));

        foreach (self::NICE_STEPS as $multiplier) {
            if ($multiplier * $magnitude >= $raw - 1e-9 * $magnitude) {
                return $multiplier * $magnitude;
            }
        }

        return 10 * $magnitude;
    }
}
