<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAnalyticBreakdown;
use App\Models\Account;
use App\Models\Operation;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OperationRequest extends FormRequest
{
    use ValidatesAnalyticBreakdown;

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
            'is_ticked' => ['sometimes', 'boolean'],
            'analytic_lines' => ['array'],
            'analytic_lines.*.analytic_axis_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('analytic_axes', 'id')
                    ->where('portfolio_id', $account->portfolio_id)
                    ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhereIn('id', $used_axis_ids)),
            ],
            'analytic_lines.*.percentage' => $this->percentageRules(),
        ];
    }

    /**
     * Contrôle, une fois les lignes valides, que la répartition analytique totalise 100 %.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->totalsOneHundredPercent('analytic_lines')];
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
            'is_ticked' => 'Pointée',
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
        return $this->breakdownMessages('analytic_lines');
    }
}
