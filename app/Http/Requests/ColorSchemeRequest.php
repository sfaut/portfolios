<?php

namespace App\Http\Requests;

use App\Enums\ColorScheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Thème choisi par l'utilisateur, envoyé par l'interrupteur de la barre ou la page Préférences.
 */
class ColorSchemeRequest extends FormRequest
{
    /**
     * Tout utilisateur connecté choisit son propre thème.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Noms des champs dans les messages d'erreur.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'color_scheme' => 'Thème',
        ];
    }

    /**
     * Règles du thème : une valeur de ColorScheme.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'color_scheme' => ['required', 'string', Rule::enum(ColorScheme::class)],
        ];
    }
}
