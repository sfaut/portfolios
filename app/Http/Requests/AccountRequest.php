<?php

namespace App\Http\Requests;

use App\Models\Account;
use App\Models\Portfolio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles du compte. Ses axes analytiques (facultatifs, distincts) se choisissent parmi ceux que
     * son portfolio lui propose : non archivés, ou archivés mais déjà affectés au compte. Le portfolio
     * vient de la route, celle de création ou celle du compte modifié.
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
            'analytic_axes' => ['sometimes', 'array'],
            'analytic_axes.*' => ['integer', 'distinct', Rule::in($axis_ids)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'description' => 'Description',
            'analytic_axes' => 'Axes analytiques',
            'analytic_axes.*' => 'Axe analytique',
        ];
    }
}
