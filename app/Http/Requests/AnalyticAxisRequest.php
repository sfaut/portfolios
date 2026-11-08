<?php

namespace App\Http\Requests;

use App\Models\AnalyticAxis;
use App\Models\Portfolio;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la création et de la modification d'un axe analytique.
 */
class AnalyticAxisRequest extends FormRequest
{
    /**
     * Autorise la requête : le cloisonnement passe par la liaison de modèle des routes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation : nom unique dans le portfolio, axes archivés compris.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var AnalyticAxis|null $analytic_axis */
        $analytic_axis = $this->route('analytic_axis');

        /** @var Portfolio|null $portfolio */
        $portfolio = $this->route('portfolio');

        $portfolio_id = $analytic_axis->portfolio_id ?? $portfolio->id;

        return [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('analytic_axes', 'name')
                    ->where('portfolio_id', $portfolio_id)
                    ->ignore($analytic_axis),
            ],
            'description' => ['string', 'max:1000'],
        ];
    }

    /**
     * Noms des champs dans les messages d'erreur.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'description' => 'Description',
        ];
    }

    /**
     * Messages d'erreur propres à cette requête.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Un axe de ce portfolio porte déjà ce nom, éventuellement parmi les axes archivés.',
        ];
    }
}
