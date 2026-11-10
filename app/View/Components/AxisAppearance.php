<?php

namespace App\View\Components;

use App\Enums\AxisColor;
use App\Support\AxisIcons;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Apparence d'un axe analytique dans son formulaire : aperçu de la pastille, couleur et icône.
 *
 * Couleurs et icônes sont des radios natifs (champs `color` et `icon`), envoyés sans JavaScript.
 * Le composant Alpine `axisAppearance` (resources/js/axis-appearance.js) y ajoute l'aperçu, qui suit
 * le nom saisi, la teinte de la grille d'icônes dans la couleur choisie, la recherche et les
 * onglets de thème. Une icône ou une couleur inconnue retombe sur celle par défaut.
 */
class AxisAppearance extends Component
{
    /**
     * Icône choisie, de la sélection.
     */
    public string $icon;

    /**
     * Couleur choisie.
     */
    public AxisColor $color;

    /**
     * Nom initial de l'axe, pour l'aperçu.
     */
    public string $name;

    /**
     * Thèmes de la sélection, avec leurs icônes : nom Lucide, nom français et texte cherché.
     *
     * @var list<array{title: string, id: string, icons: list<array{name: string, label: string, search: string}>}>
     */
    public array $themes;

    /**
     * Données du composant Alpine : choix initiaux et classes de chaque couleur.
     *
     * @var array{nameField: string, icon: string, color: string, badges: array<string, string>, texts: array<string, string>}
     */
    public array $config;

    /**
     * Normalise les choix initiaux, qui peuvent revenir d'une saisie refusée sous n'importe quel
     * type, et prépare thèmes et couleurs.
     *
     * @param  string  $nameField  Identifiant du champ du nom de l'axe, que suit l'aperçu.
     * @param  mixed  $icon  Icône initiale (nom Lucide).
     * @param  mixed  $color  Couleur initiale (valeur de AxisColor).
     * @param  mixed  $name  Nom initial de l'axe, pour l'aperçu.
     */
    public function __construct(
        public string $nameField = 'name',
        mixed $icon = AxisIcons::DEFAULT,
        mixed $color = '',
        mixed $name = '',
    ) {
        $this->icon = in_array($icon, AxisIcons::names(), strict: true) ? $icon : AxisIcons::DEFAULT;
        $this->color = (is_string($color) ? AxisColor::tryFrom($color) : null) ?? AxisColor::DEFAULT;
        $this->name = is_string($name) ? $name : '';

        $this->themes = array_map(
            fn (string $title): array => [
                'title' => $title,
                'id' => 'axis-icons-'.Str::slug($title),
                'icons' => array_map(
                    fn (string $icon, array $entry): array => [
                        'name' => $icon,
                        'label' => $entry[0],
                        'search' => "{$entry[0]} {$entry[1]} {$icon} {$title}",
                    ],
                    array_keys(AxisIcons::THEMES[$title]),
                    AxisIcons::THEMES[$title],
                ),
            ],
            array_keys(AxisIcons::THEMES),
        );

        $this->config = [
            'nameField' => $this->nameField,
            'icon' => $this->icon,
            'color' => $this->color->value,
            'badges' => array_column(array_map(fn (AxisColor $case): array => [$case->value, $case->badgeClasses()], AxisColor::cases()), 1, 0),
            'texts' => array_column(array_map(fn (AxisColor $case): array => [$case->value, $case->textClasses()], AxisColor::cases()), 1, 0),
        ];
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.axis-appearance');
    }
}
