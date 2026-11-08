<?php

namespace App\Http\Requests;

use App\Models\Account;
use App\Models\Operation;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OperationRequest extends FormRequest
{
    protected $errorBag = 'default';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation de l'opération et de sa répartition analytique facultative.
     *
     * Un axe se choisit parmi ceux du portfolio du compte, non archivés ; en modification, un axe
     * archivé déjà utilisé par l'opération reste accepté.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Operation|null $operation */
        $operation = $this->route('operation');

        /** @var Account $account */
        $account = $operation->account ?? $this->route('account');

        $used_axis_ids = $operation?->analyticLines()->pluck('analytic_axis_id')->all() ?? [];

        return [
            'label' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'max:99999999', 'decimal:0,2'],
            'delivery_at' => ['required', 'date'], // https://laravel.com/docs/11.x/validation#rule-date-format
            'analytic_lines' => ['array'],
            'analytic_lines.*.analytic_axis_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('analytic_axes', 'id')
                    ->where('portfolio_id', $account->portfolio_id)
                    ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhereIn('id', $used_axis_ids)),
            ],
            'analytic_lines.*.percentage' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
        ];
    }

    /**
     * Contrôle, une fois les lignes valides, que la répartition analytique totalise 100 %.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $lines = $this->input('analytic_lines', []);

                $has_line_errors = collect($validator->errors()->keys())
                    ->contains(fn (string $key): bool => str_starts_with($key, 'analytic_lines'));

                if ($has_line_errors || ! is_array($lines) || $lines === []) {
                    return;
                }

                $total = $this->hundredthsTotal($lines);

                if ($total !== 10000) {
                    $current = Number::percentage($total / 100, maxPrecision: 2, locale: 'fr');

                    $validator->errors()->add(
                        'analytic_lines',
                        "Le total de la répartition analytique doit faire 100 % (actuellement {$current}).",
                    );
                }
            },
        ];
    }

    /**
     * Lignes analytiques validées, prêtes à créer : axe et pourcentage seuls.
     *
     * @return list<array{analytic_axis_id: int, percentage: string}>
     */
    public function analyticLines(): array
    {
        return array_values(array_map(
            fn (array $line): array => [
                'analytic_axis_id' => (int) $line['analytic_axis_id'],
                'percentage' => (string) $line['percentage'],
            ],
            $this->validated('analytic_lines', []),
        ));
    }

    public function attributes(): array
    {
        return [
            'label' => 'Libellé',
            'amount' => 'Montant',
            'delivery_at' => 'Date effective',
            'analytic_lines' => 'Répartition analytique',
            'analytic_lines.*.analytic_axis_id' => 'Axe analytique',
            'analytic_lines.*.percentage' => 'Pourcentage',
        ];
    }

    /**
     * Messages d'erreur de la répartition analytique.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'analytic_lines.*.analytic_axis_id.required' => 'Choisissez un axe analytique.',
            'analytic_lines.*.analytic_axis_id.integer' => 'Choisissez un axe analytique.',
            'analytic_lines.*.analytic_axis_id.distinct' => 'Cet axe analytique est déjà utilisé par une autre ligne.',
            'analytic_lines.*.analytic_axis_id.exists' => 'Cet axe analytique n\'est pas disponible pour ce portfolio.',
            'analytic_lines.*.percentage.required' => 'Saisissez un pourcentage.',
            'analytic_lines.*.percentage.numeric' => 'Le pourcentage doit être un nombre.',
            'analytic_lines.*.percentage.gt' => 'Le pourcentage doit être supérieur à 0.',
            'analytic_lines.*.percentage.max' => 'Le pourcentage ne peut pas dépasser 100.',
            'analytic_lines.*.percentage.decimal' => 'Le pourcentage a au plus deux décimales.',
        ];
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
