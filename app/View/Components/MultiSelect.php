<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Sélection multiple dans une liste d'options : un déclencheur à l'allure d'un champ, et un popover
 * de cases à cocher natives, envoyées par le formulaire dans name[] (sans JavaScript aussi).
 *
 * Le déclencheur résume la sélection : le placeholder sans option cochée, le libellé de l'option
 * quand il n'y en a qu'une, puis leur nombre suivi du nom donné (« 3 comptes »). Au-delà de
 * SEARCH_THRESHOLD options, le popover a un champ qui filtre la liste, sans tenir compte de la casse
 * ni des accents.
 *
 * La valeur initiale est normalisée : seules les valeurs des options sont gardées, dans l'ordre des
 * options. Cette normalisation évite d'afficher une sélection impossible ; elle ne remplace pas le
 * contrôle de la valeur reçue, qui reste à la charge du serveur.
 *
 * En choix unique (multiple à false), les options sont des radios natifs envoyés dans name, précédés
 * d'un radio vide libellé par le placeholder (« Tout »), coché sans choix : le retour à « tout » se
 * fait aussi sans JavaScript. La sélection tient alors en une valeur au plus, et une valeur seule
 * (« sales ») est acceptée au départ comme une liste ; le parent la lie en texte, '' sans choix.
 *
 * Le composant Alpine multiSelect (resources/js/multi-select.js) tient le libellé à jour, filtre la
 * liste et mène le clavier ; le parent lie la sélection par x-model (x-modelable).
 */
class MultiSelect extends Component
{
    /**
     * Nombre d'options au-delà duquel le popover propose un champ de recherche.
     */
    public const int SEARCH_THRESHOLD = 8;

    /**
     * Options proposées, valeurs en chaînes.
     *
     * @var list<array{value: string, label: string, hint: string, icon: string, iconClass: string}>
     */
    public array $choices;

    /**
     * Valeurs cochées, parmi celles des options et dans leur ordre ; une au plus en choix unique.
     *
     * @var list<string>
     */
    public array $selection;

    /**
     * Libellé de la sélection pour le déclencheur, vide sans sélection (le placeholder s'affiche).
     */
    public string $triggerLabel;

    /**
     * Identifiant du popover, déduit du nom du champ : il relie le déclencheur au popover et nomme
     * l'ancre CSS, sans JavaScript.
     */
    public string $popoverId;

    /**
     * Si le popover propose un champ de recherche.
     */
    public bool $searchable;

    /**
     * Configuration transmise au composant Alpine multiSelect.
     *
     * @var array{value: list<string>|string, options: list<array{value: string, label: string}>, placeholder: string, noun: string, multiple: bool}
     */
    public array $config;

    /**
     * Normalise les options et la sélection, puis calcule le libellé du déclencheur.
     *
     * @param  string  $name  Nom du champ : chaque option cochée est envoyée dans name[].
     * @param  string  $label  Libellé accessible du sélecteur, que suit la sélection (« Comptes : 3 comptes »).
     * @param  list<array{value: int|string, label: string, hint?: string, icon?: string, iconClass?: string}>  $options
     *                                                                                                                    Options proposées ; hint précise une option (« archivé »), icon (nom Lucide) et iconClass
     *                                                                                                                    (classes de couleur) placent une icône devant son libellé.
     * @param  list<int|string>|mixed  $value  Valeurs cochées au départ.
     * @param  string  $placeholder  Texte du déclencheur sans option cochée.
     * @param  string  $noun  Nom au pluriel qui suit le nombre d'options cochées (« comptes »).
     * @param  bool  $multiple  Si plusieurs options se cochent (cases), sinon une seule (radios).
     */
    public function __construct(
        public string $name,
        public string $label,
        array $options = [],
        mixed $value = [],
        public string $placeholder = '',
        public string $noun = 'options',
        public bool $multiple = true,
    ) {
        $this->choices = array_map(fn (array $option): array => [
            'value' => (string) $option['value'],
            'label' => $option['label'],
            'hint' => $option['hint'] ?? '',
            'icon' => $option['icon'] ?? '',
            'iconClass' => $option['iconClass'] ?? '',
        ], array_values($options));

        $values = is_array($value) ? $value : ($multiple ? [] : [$value]);
        $checked = array_map(strval(...), array_filter($values, fn (mixed $item): bool => is_int($item) || is_string($item)));

        $this->selection = $multiple
            ? array_values(array_filter(
                array_column($this->choices, 'value'),
                fn (string $choice): bool => in_array($choice, $checked, strict: true),
            ))
            : array_slice(array_values(array_intersect($checked, array_column($this->choices, 'value'))), 0, 1);

        $this->triggerLabel = $this->describe();
        $this->popoverId = 'multi-select-'.Str::slug($this->name);
        $this->searchable = count($this->choices) > self::SEARCH_THRESHOLD;

        $this->config = [
            'value' => $multiple ? $this->selection : ($this->selection[0] ?? ''),
            'options' => array_map(fn (array $choice): array => ['value' => $choice['value'], 'label' => $choice['label']], $this->choices),
            'placeholder' => $this->placeholder,
            'noun' => $this->noun,
            'multiple' => $multiple,
        ];
    }

    /**
     * Libellé de la sélection : vide sans option cochée, le libellé de l'option seule, ou leur nombre
     * suivi du nom (« 3 comptes »).
     */
    private function describe(): string
    {
        $count = count($this->selection);

        if ($count === 0) {
            return '';
        }

        if ($count > 1) {
            return "{$count} {$this->noun}";
        }

        return collect($this->choices)->firstWhere('value', $this->selection[0])['label'];
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.multi-select');
    }
}
