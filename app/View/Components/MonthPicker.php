<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Sélecteur de mois, inspiré de <input type="month">, en trois modes : un mois (single), plusieurs
 * mois disjoints (multiple) ou une plage de mois (range).
 *
 * Les mois s'écrivent « 2026-11 ». min et max bornent les mois proposés, months les restreint en plus
 * à une liste. La classe normalise la valeur reçue (mois valides et choisissables seulement, triés,
 * plage remise dans l'ordre) et calcule le libellé initial du déclencheur, que le composant Alpine
 * monthPicker (resources/js/month-picker.js) recalcule ensuite à l'identique.
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
     * Forme d'un mois : année sur quatre chiffres, tiret, mois sur deux chiffres.
     */
    public const string MONTH_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/';

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
     * Configuration transmise au composant Alpine monthPicker.
     *
     * @var array{mode: string, value: string|list<string>|array{start: string, end: string}, months: list<string>|null, min: string, max: string, placeholder: string}
     */
    public array $config;

    /**
     * Retient le mode, ou single s'il est inconnu, déduit des mois choisissables les bornes absentes,
     * puis normalise la sélection et en calcule le libellé.
     *
     * @param  string  $name  Nom du champ envoyé : name (single), name[] (multiple), name[start] et name[end] (range).
     * @param  string  $label  Libellé accessible du sélecteur, que suit la sélection (« Mois d'opération : Novembre 2026 »).
     * @param  string  $mode  Mode de sélection : single (par défaut), multiple ou range.
     * @param  string|list<string>|array{start?: string, end?: string}|null  $value
     *                                                                               Sélection initiale, sous la forme du mode.
     * @param  list<string>|null  $months
     *                                     Seuls mois choisissables, ou null pour tous ceux des bornes.
     * @param  string  $min  Premier mois proposé, vide sans borne (déduit de months à défaut).
     * @param  string  $max  Dernier mois proposé, vide sans borne (déduit de months à défaut).
     * @param  string  $placeholder  Texte du déclencheur sans sélection, aussi celui du bouton qui l'efface.
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $mode = 'single',
        public string|array|null $value = null,
        public ?array $months = null,
        public string $min = '',
        public string $max = '',
        public string $placeholder = '',
    ) {
        if (! in_array($this->mode, self::MODES, true)) {
            $this->mode = self::MODES[0];
        }

        if ($this->months !== null) {
            $this->months = collect($this->months)->filter(self::isMonth(...))->unique()->sort()->values()->all();
            $this->min = $this->min !== '' ? $this->min : ($this->months[0] ?? '');
            $this->max = $this->max !== '' ? $this->max : ($this->months[count($this->months) - 1] ?? '');
        }

        $this->selection = $this->normalize($this->value);
        $this->triggerLabel = $this->describe($this->selection);

        $this->config = [
            'mode' => $this->mode,
            'value' => $this->selection,
            'months' => $this->months,
            'min' => $this->min,
            'max' => $this->max,
            'placeholder' => $this->placeholder,
        ];
    }

    /**
     * Si la valeur est un mois de la forme « 2026-11 ».
     *
     * @param  mixed  $value  Valeur à vérifier.
     */
    public static function isMonth(mixed $value): bool
    {
        return is_string($value) && preg_match(self::MONTH_PATTERN, $value) === 1;
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
     * Si le mois peut être choisi : dans les bornes, et dans la liste des mois quand elle est donnée.
     *
     * @param  mixed  $month  Mois à vérifier.
     */
    private function isChoosable(mixed $month): bool
    {
        return self::isMonth($month)
            && ($this->min === '' || $month >= $this->min)
            && ($this->max === '' || $month <= $this->max)
            && ($this->months === null || in_array($month, $this->months, true));
    }

    /**
     * Sélection de la forme du mode, faite des seuls mois choisissables.
     *
     * @param  string|list<string>|array{start?: string, end?: string}|null  $value
     *                                                                               Sélection reçue.
     * @return string|list<string>|array{start: string, end: string}
     */
    private function normalize(string|array|null $value): string|array
    {
        if ($this->mode === 'multiple') {
            return collect(is_array($value) ? $value : [$value])
                ->filter($this->isChoosable(...))->unique()->sort()->values()->all();
        }

        if ($this->mode === 'range') {
            $bounds = collect(is_array($value) ? [$value['start'] ?? null, $value['end'] ?? null] : [$value])
                ->filter($this->isChoosable(...))->sort()->values();

            return ['start' => $bounds->first() ?? '', 'end' => $bounds->count() > 1 ? $bounds->last() : ''];
        }

        return $this->isChoosable($value) ? $value : '';
    }

    /**
     * Libellé d'une sélection normalisée, vide sans sélection.
     *
     * @param  string|list<string>|array{start: string, end: string}  $selection
     *                                                                            Sélection de la forme du mode.
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
