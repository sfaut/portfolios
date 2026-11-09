<?php

namespace App\Rules;

use App\Support\DateRestriction;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

/**
 * Règle de validation de la valeur envoyée par un sélecteur de date (<x-date-picker>) : elle refuse
 * toute valeur qui n'a pas la forme du mode ou qui contient un jour que la restriction interdit.
 *
 * La restriction doit être celle passée au composant, construite par le serveur (voir
 * App\View\Components\DatePicker) : le contrôle ne dépend ainsi jamais de ce que le navigateur a
 * affiché ou envoyé.
 *
 * Formes acceptées :
 * - single : un jour choisissable, « 2026-11-09 » ;
 * - multiple : une liste de jours choisissables, sans doublon ;
 * - range : un tableau [start, end] de deux jours choisissables, start ≤ end. Les jours entre les deux
 *   peuvent être interdits : seules les bornes comptent.
 *
 * Comme toute règle non implicite, elle ne s'applique pas à une valeur absente ou vide ('' ou []) :
 * l'exiger revient à `required`. En mode range, le composant envoie toujours start et end, vides sans
 * plage : une plage vide passe pour absente, et l'exiger se fait sur le début
 * (`'period.start' => ['required']`).
 */
class DateSelection implements ValidationRule
{
    /**
     * Retient le mode et la restriction à appliquer.
     *
     * @param  string  $mode  Mode du sélecteur : single, multiple ou range.
     * @param  DateRestriction  $restriction  Restriction des jours, la même que celle du composant ; saisie libre par défaut.
     *
     * @throws InvalidArgumentException Si le mode est inconnu.
     */
    public function __construct(
        private string $mode,
        private DateRestriction $restriction = new DateRestriction,
    ) {
        if (! in_array($this->mode, ['single', 'multiple', 'range'], true)) {
            throw new InvalidArgumentException("Le mode « {$this->mode} » n'est pas un mode de sélection de date.");
        }
    }

    /**
     * Refuse la valeur si elle n'a pas la forme du mode ou contient un jour interdit.
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
     * Mode single : un jour choisissable.
     *
     * @param  mixed  $value  Valeur reçue.
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail  Signale le refus.
     */
    private function validateSingle(mixed $value, Closure $fail): void
    {
        if (! $this->restriction->isChoosable($value)) {
            $fail('Le champ « :attribute » doit être une date autorisée.');
        }
    }

    /**
     * Mode multiple : une liste de jours choisissables, sans doublon.
     *
     * @param  mixed  $value  Valeur reçue.
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail  Signale le refus.
     */
    private function validateMultiple(mixed $value, Closure $fail): void
    {
        if (! is_array($value) || ! array_is_list($value)) {
            $fail('Le champ « :attribute » doit être une liste de dates.');

            return;
        }

        if (array_any($value, fn (mixed $day): bool => ! $this->restriction->isChoosable($day))) {
            $fail('Le champ « :attribute » ne doit contenir que des dates autorisées.');

            return;
        }

        if (count(array_unique($value)) !== count($value)) {
            $fail('Le champ « :attribute » contient une date en double.');
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
            $fail('Le champ « :attribute » doit être une plage de dates (début et fin).');

            return;
        }

        $start = $value['start'] ?? '';
        $end = $value['end'] ?? '';

        if ($start === '' && $end === '') {
            return;
        }

        if (! $this->restriction->isChoosable($start) || ! $this->restriction->isChoosable($end)) {
            $fail('Le début et la fin du champ « :attribute » doivent être des dates autorisées.');

            return;
        }

        if ($start > $end) {
            $fail('Le début du champ « :attribute » doit précéder sa fin.');
        }
    }
}
