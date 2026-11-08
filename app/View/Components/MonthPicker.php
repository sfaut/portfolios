<?php

namespace App\View\Components;

use App\Support\MonthRestriction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Sélecteur de mois, inspiré de <input type="month">, en trois modes : un mois (single), plusieurs
 * mois disjoints (multiple) ou une plage de mois (range).
 *
 * Valeur et champs envoyés, selon le mode :
 * - single : « 2026-11 », ou '' sans mois ; champ name ;
 * - multiple : liste triée de mois ; un champ name[] par mois ;
 * - range : début et fin ('' s'ils manquent) ; champs name[start] et name[end].
 *
 * Saisie libre ou restreinte : la restriction (App\Support\MonthRestriction) borne les mois (min,
 * max) et les restreint par une liste blanche (allow) et une liste noire (deny) de mois et de plages
 * (« 2025-01/2025-06 »). Sans restriction, tout mois est choisissable.
 *
 * Validation : comment le serveur sait que la valeur reçue est correcte.
 *
 * 1. La restriction est construite par le serveur, dans le contrôleur ou la FormRequest, jamais lue
 *    depuis la requête. C'est la seule source de vérité.
 * 2. Affichage : le composant en envoie une copie au navigateur (config.restriction), que le
 *    composant Alpine monthPicker utilise pour griser les mois interdits et les sauter au clavier.
 *    Cette copie ne sert qu'au confort de saisie ; la modifier dans le navigateur ne change rien
 *    côté serveur.
 * 3. Contrôle : à l'envoi, la règle App\Rules\MonthSelection vérifie la valeur reçue contre la même
 *    restriction, reconstruite à chaque requête, et la refuse si elle n'a pas la forme du mode ou
 *    contient un mois interdit (requête forgée, champ modifié, JavaScript contourné). Le formulaire
 *    revient alors avec l'erreur (ou une réponse 422 en JSON).
 *
 * Exemple, une FormRequest qui partage la restriction entre sa règle et la vue :
 *
 *     public function monthRestriction(): MonthRestriction
 *     {
 *         return new MonthRestriction(allow: ['2025-01/2025-12'], deny: ['2025-08']);
 *     }
 *
 *     public function rules(): array
 *     {
 *         return ['period' => ['required', new MonthSelection('range', $this->monthRestriction())]];
 *     }
 *
 *     <x-month-picker name="period" mode="range" :restriction="$restriction" label="Période"/>
 *
 * Un filtre en GET (page d'un portfolio) peut préférer ignorer une valeur refusée plutôt que
 * répondre par une erreur : il applique alors MonthRestriction::isChoosable() lui-même.
 *
 * La valeur initiale passée au composant (old(), filtre en cours…) est normalisée : seuls ses mois
 * choisissables sont gardés, triés, et une plage est remise dans l'ordre. Cette normalisation évite
 * d'afficher une sélection impossible ; elle ne remplace pas le contrôle à l'envoi.
 *
 * La classe calcule aussi le libellé initial du déclencheur, que le composant Alpine
 * (resources/js/month-picker.js) recalcule ensuite à l'identique.
 */
class MonthPicker extends Component
{
    /**
     * Modes de sélection, le premier par défaut.
     *
     * @var list<string>
     */
    public const array MODES = ['single', 'multiple', 'range'];

    /**
     * Restriction appliquée, saisie libre sans restriction donnée.
     */
    public MonthRestriction $restriction;

    /**
     * Sélection normalisée : un mois ou '' (single), une liste triée (multiple), un début et une fin,
     * '' s'ils manquent (range).
     *
     * @var string|list<string>|array{start: string, end: string}
     */
    public string|array $selection;

    /**
     * Libellé de la sélection pour le déclencheur, vide sans sélection (le placeholder s'affiche).
     */
    public string $triggerLabel;

    /**
     * Configuration transmise au composant Alpine monthPicker ; sa restriction n'est qu'une copie pour
     * l'affichage.
     *
     * @var array{mode: string, value: string|list<string>|array{start: string, end: string}, restriction: array<string, mixed>, placeholder: string}
     */
    public array $config;

    /**
     * Retient le mode, ou single s'il est inconnu, et la restriction, ou la saisie libre sans
     * restriction, puis normalise la sélection et en calcule le libellé.
     *
     * @param  string  $name  Nom du champ envoyé : name (single), name[] (multiple), name[start] et name[end] (range).
     * @param  string  $label  Libellé accessible du sélecteur, que suit la sélection (« Mois d'opération : Novembre 2026 »).
     * @param  string  $mode  Mode de sélection : single (par défaut), multiple ou range.
     * @param  string|list<string>|array{start?: string, end?: string}|null  $value  Sélection initiale, sous la forme du mode.
     * @param  MonthRestriction|null  $restriction  Restriction des mois, la même que celle de la règle MonthSelection ; null pour la saisie libre.
     * @param  string  $placeholder  Texte du déclencheur sans sélection, aussi celui du bouton qui l'efface.
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $mode = 'single',
        public string|array|null $value = null,
        ?MonthRestriction $restriction = null,
        public string $placeholder = '',
    ) {
        if (! in_array($this->mode, self::MODES, true)) {
            $this->mode = self::MODES[0];
        }

        $this->restriction = $restriction ?? new MonthRestriction;
        $this->selection = $this->normalize($this->value);
        $this->triggerLabel = $this->describe($this->selection);

        $this->config = [
            'mode' => $this->mode,
            'value' => $this->selection,
            'restriction' => $this->restriction->toArray(),
            'placeholder' => $this->placeholder,
        ];
    }

    /**
     * Libellé d'un mois : « Novembre 2026 ».
     *
     * @param  string  $month  Mois « 2026-11 ».
     */
    public static function monthLabel(string $month): string
    {
        return Str::ucfirst(Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y'));
    }

    /**
     * Sélection de la forme du mode, faite des seuls mois choisissables.
     *
     * @param  string|list<string>|array{start?: string, end?: string}|null  $value  Sélection reçue.
     * @return string|list<string>|array{start: string, end: string}
     */
    private function normalize(string|array|null $value): string|array
    {
        if ($this->mode === 'multiple') {
            return collect(is_array($value) ? $value : [$value])
                ->filter($this->restriction->isChoosable(...))->unique()->sort()->values()->all();
        }

        if ($this->mode === 'range') {
            $bounds = collect(is_array($value) ? [$value['start'] ?? null, $value['end'] ?? null] : [$value])
                ->filter($this->restriction->isChoosable(...))->sort()->values();

            return ['start' => $bounds->first() ?? '', 'end' => $bounds->count() > 1 ? $bounds->last() : ''];
        }

        return $this->restriction->isChoosable($value) ? $value : '';
    }

    /**
     * Libellé d'une sélection normalisée, vide sans sélection.
     *
     * @param  string|list<string>|array{start: string, end: string}  $selection  Sélection de la forme du mode.
     */
    private function describe(string|array $selection): string
    {
        if ($this->mode === 'multiple') {
            $count = count($selection);

            return match ($count) {
                0 => '',
                1 => self::monthLabel($selection[0]),
                default => "{$count} mois",
            };
        }

        if ($this->mode === 'range') {
            ['start' => $start, 'end' => $end] = $selection;

            if ($start === '') {
                return '';
            }

            $start_label = self::monthLabel($start);
            $start_name = Str::lower($start_label);
            $end_name = $end === '' ? '' : Str::lower(self::monthLabel($end));

            return match ($end) {
                '' => "Depuis {$start_name}",
                $start => $start_label,
                default => "{$start_label} – {$end_name}",
            };
        }

        return $selection === '' ? '' : self::monthLabel($selection);
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.month-picker');
    }
}
