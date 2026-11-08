<?php

namespace App\Rules;

use App\Support\MonthRestriction;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

/**
 * Règle de validation de la valeur envoyée par un sélecteur de mois (<x-month-picker>) : elle refuse
 * toute valeur qui n'a pas la forme du mode ou qui contient un mois que la restriction interdit.
 *
 * La restriction doit être celle passée au composant, construite par le serveur (voir
 * App\View\Components\MonthPicker) : le contrôle ne dépend ainsi jamais de ce que le navigateur a
 * affiché ou envoyé.
 *
 * Formes acceptées :
 * - single : un mois choisissable, « 2026-11 » ;
 * - multiple : une liste de mois choisissables, sans doublon ;
 * - range : un tableau [start, end] de deux mois choisissables, start ≤ end. Les mois entre les deux
 *   peuvent être interdits : seules les bornes comptent.
 *
 * Comme toute règle non implicite, elle ne s'applique pas à une valeur absente ou vide ('' ou []) :
 * l'exiger revient à `required`. En mode range, le composant envoie toujours start et end, vides sans
 * plage : une plage vide passe pour absente, et l'exiger se fait sur le début
 * (`'period.start' => ['required']`).
 */
class MonthSelection implements ValidationRule
{
    /**
     * Retient le mode et la restriction à appliquer.
     *
     * @param  string  $mode  Mode du sélecteur : single, multiple ou range.
     * @param  MonthRestriction  $restriction  Restriction des mois, la même que celle du composant ; saisie libre par défaut.
     *
     * @throws InvalidArgumentException Si le mode est inconnu.
     */
    public function __construct(
        private string $mode,
        private MonthRestriction $restriction = new MonthRestriction,
    ) {
        if (! in_array($this->mode, ['single', 'multiple', 'range'], true)) {
            throw new InvalidArgumentException("Le mode « {$this->mode} » n'est pas un mode de sélection de mois.");
        }
    }

    /**
     * Refuse la valeur si elle n'a pas la forme du mode ou contient un mois interdit.
     *
     * @param  string  $attribute  Nom du champ.
     * @param  mixed  $value  Valeur reçue, de n'importe quel type.
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail  Signale le refus, avec son message.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        match ($this->mode) {
            'multiple' => $this->validateMultiple($value, $fail),
            'range' => $this->validateRange($value, $fail),
            default => $this->validateSingle($value, $fail),
        };
    }

    /**
     * Mode single : un mois choisissable.
     *
     * @param  mixed  $value  Valeur reçue.
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail  Signale le refus.
     */
    private function validateSingle(mixed $value, Closure $fail): void
    {
        if (! $this->restriction->isChoosable($value)) {
            $fail('Le champ « :attribute » doit être un mois autorisé.');
        }
    }

    /**
     * Mode multiple : une liste de mois choisissables, sans doublon.
     *
     * @param  mixed  $value  Valeur reçue.
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail  Signale le refus.
     */
    private function validateMultiple(mixed $value, Closure $fail): void
    {
        if (! is_array($value) || ! array_is_list($value)) {
            $fail('Le champ « :attribute » doit être une liste de mois.');

            return;
        }

        if (array_any($value, fn (mixed $month): bool => ! $this->restriction->isChoosable($month))) {
            $fail('Le champ « :attribute » ne doit contenir que des mois autorisés.');

            return;
        }

        if (count(array_unique($value)) !== count($value)) {
            $fail('Le champ « :attribute » contient un mois en double.');
        }
    }

    /**
     * Mode range : un début et une fin choisissables, le début au plus tard à la fin ; une plage dont
     * le début et la fin sont vides passe pour absente.
     *
     * @param  mixed  $value  Valeur reçue.
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail  Signale le refus.
     */
    private function validateRange(mixed $value, Closure $fail): void
    {
        if (! is_array($value) || array_diff(array_keys($value), ['start', 'end']) !== []) {
            $fail('Le champ « :attribute » doit être une plage de mois (début et fin).');

            return;
        }

        $start = $value['start'] ?? '';
        $end = $value['end'] ?? '';

        if ($start === '' && $end === '') {
            return;
        }

        if (! $this->restriction->isChoosable($start) || ! $this->restriction->isChoosable($end)) {
            $fail('Le début et la fin du champ « :attribute » doivent être des mois autorisés.');

            return;
        }

        if ($start > $end) {
            $fail('Le début du champ « :attribute » doit précéder sa fin.');
        }
    }
}
