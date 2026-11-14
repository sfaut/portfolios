<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAnalyticBreakdown;
use App\Models\Account;
use App\Models\Portfolio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AccountRequest extends FormRequest
{
    use ValidatesAnalyticBreakdown;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles du compte et de sa répartition analytique par défaut, facultative : des lignes (axe,
     * pourcentage) dans l'ordre voulu, sur des axes distincts que le portfolio lui propose (non
     * archivés, ou archivés mais déjà affectés au compte). Le portfolio vient de la route, celle de
     * création ou celle du compte modifié.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $account = $this->route('account');
        $portfolio = $account instanceof Account ? $account->portfolio : $this->route('portfolio');

        $axis_ids = $portfolio instanceof Portfolio
            ? $portfolio->assignableAnalyticAxes($account instanceof Account ? $account : null)->modelKeys()
            : [];

        return [
            'name' => ['required', 'max:100'],
            'description' => ['max:1000'],
            'analytic_axes' => ['sometimes', 'array', 'list'],
            'analytic_axes.*' => ['array'],
            'analytic_axes.*.analytic_axis_id' => ['required', 'integer', 'distinct', Rule::in($axis_ids)],
            'analytic_axes.*.percentage' => $this->percentageRules(),
        ];
    }

    /**
     * Contrôle, une fois les lignes valides, que la répartition par défaut totalise 100 %.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->totalsOneHundredPercent('analytic_axes')];
    }

    /**
     * Répartition par défaut validée, prête pour sync() : par axe, sa part et sa position, qui est
     * l'ordre d'envoi des lignes.
     *
     * @return array<int, array{percentage: string, position: int}>
     */
    public function analyticAxes(): array
    {
        $axes = [];

        foreach (array_values($this->validated('analytic_axes', [])) as $position => $line) {
            $axes[(int) $line['analytic_axis_id']] = [
                'percentage' => (string) $line['percentage'],
                'position' => $position,
            ];
        }

        return $axes;
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'description' => 'Description',
            'analytic_axes' => 'Répartition analytique',
            'analytic_axes.*' => 'Ligne analytique',
            'analytic_axes.*.analytic_axis_id' => 'Axe analytique',
            'analytic_axes.*.percentage' => 'Pourcentage',
        ];
    }

    /**
     * Messages d'erreur de la répartition par défaut.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->breakdownMessages('analytic_axes');
    }
}
