<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pointage d'une opération, envoyé par sa case à cocher (<x-tick-box>).
 *
 * La requête porte l'état voulu, et non une inversion de l'état enregistré : un double envoi ou une
 * annulation donne toujours l'état demandé.
 */
class OperationTickRequest extends FormRequest
{
    /**
     * L'appartenance de l'opération est contrôlée par la liaison de modèle de la route.
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
            'is_ticked' => 'Pointée',
        ];
    }

    /**
     * Règles du pointage : un booléen.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'is_ticked' => ['required', 'boolean'],
        ];
    }
}
