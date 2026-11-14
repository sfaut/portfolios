<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Number;
use Illuminate\Validation\Validator;

/**
 * Contrôle d'une répartition analytique : des lignes (axe, pourcentage) qui, présentes,
 * totalisent exactement 100 %. Partagé par la répartition d'une opération et celle, par défaut,
 * d'un compte.
 *
 * Le total se calcule en centièmes entiers, pour une comparaison exacte, comme le formulaire
 * (resources/js/analytic-lines.js).
 */
trait ValidatesAnalyticBreakdown
{
    /**
     * Contrôle à passer à after() : une fois les lignes valides, leur total doit faire 100 %.
     * Aucune ligne est accepté ; l'erreur va sous la clef du champ.
     *
     * @param  string  $field  Champ des lignes (analytic_lines, analytic_axes).
     * @return callable(Validator): void
     */
    protected function totalsOneHundredPercent(string $field): callable
    {
        return function (Validator $validator) use ($field): void {
            $lines = $this->input($field, []);

            $has_line_errors = collect($validator->errors()->keys())
                ->contains(fn (string $key): bool => $key === $field || str_starts_with($key, "{$field}."));

            if ($has_line_errors || ! is_array($lines) || $lines === []) {
                return;
            }

            $total = $this->hundredthsTotal($lines);

            if ($total !== 10000) {
                $current = Number::percentage($total / 100, maxPrecision: 2, locale: 'fr');

                $validator->errors()->add(
                    $field,
                    "Le total de la répartition analytique doit faire 100 % (actuellement {$current}).",
                );
            }
        };
    }

    /**
     * Messages d'erreur des lignes d'une répartition.
     *
     * @param  string  $field  Champ des lignes.
     * @return array<string, string>
     */
    protected function breakdownMessages(string $field): array
    {
        return [
            "{$field}.*.analytic_axis_id.required" => 'Choisissez un axe analytique.',
            "{$field}.*.analytic_axis_id.integer" => 'Choisissez un axe analytique.',
            "{$field}.*.analytic_axis_id.distinct" => 'Cet axe analytique est déjà utilisé par une autre ligne.',
            "{$field}.*.analytic_axis_id.exists" => 'Cet axe analytique n\'est pas disponible pour ce portfolio.',
            "{$field}.*.analytic_axis_id.in" => 'Cet axe analytique n\'est pas disponible pour ce portfolio.',
            "{$field}.*.percentage.required" => 'Saisissez un pourcentage.',
            "{$field}.*.percentage.numeric" => 'Le pourcentage doit être un nombre.',
            "{$field}.*.percentage.gt" => 'Le pourcentage doit être supérieur à 0.',
            "{$field}.*.percentage.max" => 'Le pourcentage ne peut pas dépasser 100.',
            "{$field}.*.percentage.decimal" => 'Le pourcentage a au plus deux décimales.',
        ];
    }

    /**
     * Règles du pourcentage d'une ligne : positif, 100 au plus, deux décimales au plus.
     *
     * @return list<string>
     */
    protected function percentageRules(): array
    {
        return ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'];
    }

    /**
     * Total des pourcentages en centièmes entiers, pour une comparaison exacte à 100 %.
     *
     * @param  array<array-key, mixed>  $lines  Lignes analytiques saisies, déjà validées.
     */
    private function hundredthsTotal(array $lines): int
    {
        return array_sum(array_map(
            fn (array $line): int => (int) round((float) $line['percentage'] * 100),
            $lines,
        ));
    }
}
