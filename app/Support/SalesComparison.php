<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Total des ventes d'une période, comparé à celui d'une période de référence (un an plus tôt).
 */
final readonly class SalesComparison
{
    /**
     * @param  string  $label  Libellé de la période : « ventes d'octobre », « de janvier à novembre 2026 ».
     * @param  float  $amount  Total des ventes de la période, 0 sans vente.
     * @param  float|null  $previous  Total des ventes de la période de référence, null sans vente.
     * @param  string  $reference
     *                             Période de référence, à écrire après « sur » : « octobre 2025 », « les 12 mois précédents ».
     */
    public function __construct(
        public string $label,
        public float $amount,
        public ?float $previous,
        public string $reference,
    ) {
        //
    }

    /**
     * Évolution par rapport à la référence, en pourcentage entier signé (« +8 % », « −3 % », « 0 % »),
     * ou null quand la référence n'a pas de vente. Le moins est typographique (U+2212), comme celui
     * des montants.
     */
    public function change(): ?string
    {
        $percent = $this->percent();

        if ($percent === null) {
            return null;
        }

        $sign = match (true) {
            $percent > 0 => '+',
            $percent < 0 => "\u{2212}",
            default => '',
        };

        return $sign.Number::format(abs($percent), locale: 'fr')."\u{202F}%";
    }

    /**
     * Tendance, d'après l'évolution arrondie : up (hausse), down (baisse), flat (0 %), ou null quand
     * la référence n'a pas de vente.
     */
    public function trend(): ?string
    {
        return match (true) {
            $this->percent() === null => null,
            $this->percent() > 0 => 'up',
            $this->percent() < 0 => 'down',
            default => 'flat',
        };
    }

    /**
     * Évolution en toutes lettres, pour les lecteurs d'écran : « en hausse de 8 % », « en baisse de
     * 3 % », « stable », ou '' quand la référence n'a pas de vente.
     */
    public function spokenChange(): string
    {
        $percent = $this->percent();
        $amount = $percent === null ? '' : Number::format(abs($percent), locale: 'fr')."\u{202F}%";

        return match ($this->trend()) {
            'up' => "en hausse de {$amount}",
            'down' => "en baisse de {$amount}",
            'flat' => 'stable',
            default => '',
        };
    }

    /**
     * Phrase de comparaison : « +8 % sur octobre 2025 », ou « aucune vente sur octobre 2025 ».
     */
    public function sentence(): string
    {
        $change = $this->change();

        return $change === null
            ? "aucune vente sur {$this->reference}"
            : "{$change} sur {$this->reference}";
    }

    /**
     * Évolution par rapport à la référence, en pourcentage entier arrondi, ou null quand la référence
     * n'a pas de vente.
     */
    private function percent(): ?int
    {
        return $this->previous === null
            ? null
            : (int) round(($this->amount - $this->previous) / $this->previous * 100);
    }
}
