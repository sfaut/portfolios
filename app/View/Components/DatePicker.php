<?php

namespace App\View\Components;

use App\Enums\DatePreset;
use App\Support\DateRestriction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

/**
 * Sélecteur de date, inspiré de <input type="date">, en trois modes : un jour (single), plusieurs
 * jours disjoints (multiple) ou une plage de jours (range). Il suit <x-month-picker>
 * (App\View\Components\MonthPicker), qui fait de même pour les mois.
 *
 * Valeur et champs envoyés, selon le mode :
 * - single : « 2026-11-09 », ou '' sans jour ; champ name ;
 * - multiple : liste triée de jours ; un champ name[] par jour ;
 * - range : début et fin ('' s'ils manquent), égaux pour un seul jour ; champs name[start] et
 *   name[end].
 *
 * Saisie libre ou restreinte : la restriction (App\Support\DateRestriction) borne les jours (min,
 * max) et les restreint par une liste blanche (allow) et une liste noire (deny) de jours et de plages
 * (« 2026-01-01/2026-01-31 »). Sans restriction, tout jour est choisissable.
 *
 * Validation : comment le serveur sait que la valeur reçue est correcte.
 *
 * 1. La restriction est construite par le serveur, dans le contrôleur ou la FormRequest, jamais lue
 *    depuis la requête. C'est la seule source de vérité.
 * 2. Affichage : le composant en envoie une copie au navigateur (config.restriction), que le
 *    composant Alpine datePicker utilise pour griser les jours interdits et les sauter au clavier.
 *    Cette copie ne sert qu'au confort de saisie ; la modifier dans le navigateur ne change rien
 *    côté serveur.
 * 3. Contrôle : à l'envoi, la règle App\Rules\DateSelection vérifie la valeur reçue contre la même
 *    restriction, reconstruite à chaque requête, et la refuse si elle n'a pas la forme du mode ou
 *    contient un jour interdit (requête forgée, champ modifié, JavaScript contourné).
 *
 * Exemple, une FormRequest qui partage la restriction entre sa règle et la vue :
 *
 *     public function dateRestriction(): DateRestriction
 *     {
 *         return new DateRestriction(min: '2026-01-01', deny: ['2026-05-01']);
 *     }
 *
 *     public function rules(): array
 *     {
 *         return ['period' => ['required', new DateSelection('range', $this->dateRestriction())]];
 *     }
 *
 *     <x-date-picker name="period" mode="range" :restriction="$restriction" label="Période"/>
 *
 * Un filtre en GET (grand livre) peut préférer ignorer une valeur refusée plutôt que répondre par une
 * erreur : il applique alors DateRestriction::isChoosable() lui-même.
 *
 * Préréglages (mode range) : `presets` propose des plages toutes faites (App\Enums\DatePreset),
 * calculées ici au jour courant. Une plage est ramenée dans les bornes de la restriction ; elle
 * n'est proposée que si le début et la fin obtenus sont choisissables.
 *
 * La valeur initiale passée au composant (old(), filtre en cours…) est normalisée : seuls ses jours
 * choisissables sont gardés, triés, et une plage est remise dans l'ordre. Cette normalisation évite
 * d'afficher une sélection impossible ; elle ne remplace pas le contrôle à l'envoi.
 *
 * La classe calcule aussi le libellé initial du déclencheur, que le composant Alpine
 * (resources/js/date-picker.js) recalcule ensuite à l'identique.
 */
class DatePicker extends Component
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
    public DateRestriction $restriction;

    /**
     * Sélection normalisée : un jour ou '' (single), une liste triée (multiple), un début et une fin,
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
     * Plages préréglées proposées, vide hors du mode range.
     *
     * @var list<array{label: string, start: string, end: string}>
     */
    public array $ranges;

    /**
     * Configuration transmise au composant Alpine datePicker ; sa restriction n'est qu'une copie pour
     * l'affichage.
     *
     * @var array{mode: string, value: string|list<string>|array{start: string, end: string}, restriction: array<string, mixed>, placeholder: string, presets: list<array{label: string, start: string, end: string}>}
     */
    public array $config;

    /**
     * Retient le mode, ou single s'il est inconnu, et la restriction, ou la saisie libre sans
     * restriction, puis normalise la sélection, en calcule le libellé et prépare les préréglages.
     *
     * @param  string  $name  Nom du champ envoyé : name (single), name[] (multiple), name[start] et name[end] (range).
     * @param  string  $label  Libellé accessible du sélecteur, que suit la sélection (« Date effective : 9 nov. 2026 »).
     * @param  string  $mode  Mode de sélection : single (par défaut), multiple ou range.
     * @param  string|list<string>|array{start?: string, end?: string}|null  $value  Sélection initiale, sous la forme du mode.
     * @param  DateRestriction|null  $restriction  Restriction des jours, la même que celle de la règle DateSelection ; null pour la saisie libre.
     * @param  string  $placeholder  Texte du déclencheur sans sélection, aussi celui du bouton qui l'efface.
     * @param  list<DatePreset>  $presets  Plages préréglées à proposer, en mode range.
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $mode = 'single',
        public string|array|null $value = null,
        ?DateRestriction $restriction = null,
        public string $placeholder = '',
        public array $presets = [],
    ) {
        if (! in_array($this->mode, self::MODES, true)) {
            $this->mode = self::MODES[0];
        }

        $this->restriction = $restriction ?? new DateRestriction;
        $this->selection = $this->normalize($this->value);
        $this->triggerLabel = $this->describe($this->selection);
        $this->ranges = $this->mode === 'range' ? $this->presetRanges(CarbonImmutable::today()) : [];

        $this->config = [
            'mode' => $this->mode,
            'value' => $this->selection,
            'restriction' => $this->restriction->toArray(),
            'placeholder' => $this->placeholder,
            'presets' => $this->ranges,
        ];
    }

    /**
     * Libellé d'un jour : « 9 nov. 2026 ».
     *
     * @param  string  $day  Jour « 2026-11-09 ».
     */
    public static function dayLabel(string $day): string
    {
        return Carbon::createFromFormat('!Y-m-d', $day)->translatedFormat('j M Y');
    }

    /**
     * Libellé d'une plage de jours, ce que le début et la fin partagent n'étant écrit qu'une fois :
     * « 9 nov. 2026 », « 1 – 30 nov. 2026 », « 28 oct. – 9 nov. 2026 », « 28 déc. 2025 – 9 janv. 2026 ».
     *
     * @param  string  $start  Premier jour, « 2026-11-01 ».
     * @param  string  $end  Dernier jour, « 2026-11-30 ».
     */
    public static function rangeLabel(string $start, string $end): string
    {
        $end_label = self::dayLabel($end);
        $first = Carbon::createFromFormat('!Y-m-d', $start);

        return match (true) {
            $start === $end => $end_label,
            mb_substr($start, 0, 7) === mb_substr($end, 0, 7) => "{$first->translatedFormat('j')} – {$end_label}",
            mb_substr($start, 0, 4) === mb_substr($end, 0, 4) => "{$first->translatedFormat('j M')} – {$end_label}",
            default => self::dayLabel($start)." – {$end_label}",
        };
    }

    /**
     * Sélection de la forme du mode, faite des seuls jours choisissables.
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
                1 => self::dayLabel($selection[0]),
                default => "{$count} jours",
            };
        }

        if ($this->mode === 'range') {
            ['start' => $start, 'end' => $end] = $selection;

            return match (true) {
                $start === '' => '',
                $end === '' => 'Depuis le '.self::dayLabel($start),
                default => self::rangeLabel($start, $end),
            };
        }

        return $selection === '' ? '' : self::dayLabel($selection);
    }

    /**
     * Plages préréglées proposées : chacune ramenée dans les bornes de la restriction, et gardée si
     * le début et la fin obtenus sont choisissables.
     *
     * @param  CarbonImmutable  $today  Jour courant.
     * @return list<array{label: string, start: string, end: string}>
     */
    private function presetRanges(CarbonImmutable $today): array
    {
        $ranges = [];

        foreach ($this->presets as $preset) {
            ['start' => $start, 'end' => $end] = $preset->range($today);

            $start = $this->restriction->min === '' ? $start : max($start, $this->restriction->min);
            $end = $this->restriction->max === '' ? $end : min($end, $this->restriction->max);

            if ($start <= $end && $this->restriction->isChoosable($start) && $this->restriction->isChoosable($end)) {
                $ranges[] = ['label' => $preset->label(), 'start' => $start, 'end' => $end];
            }
        }

        return $ranges;
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.date-picker');
    }
}
