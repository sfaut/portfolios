<?php

namespace App\View\Components;

use App\Enums\AxisColor;
use App\Models\AnalyticAxis;
use App\Support\AxisIcons;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Pastille d'un axe analytique : son icône devant son nom, aux couleurs de l'axe, et « (archivé) »
 * pour un axe archivé. Avec `icon-only`, l'icône colorée seule, pour une infobulle ou une liste,
 * et `on-dark` sur un fond toujours sombre.
 *
 * L'axe est un modèle, ou un tableau / objet issu d'une requête toBase() qui porte name, icon,
 * color et, facultatif, archived (ou deleted_at). Une icône ou une couleur vide ou inconnue retombe
 * sur celle par défaut.
 */
class AxisBadge extends Component
{
    /**
     * Nom de l'axe.
     */
    public string $name;

    /**
     * Composant de l'icône (lucide-…).
     */
    public string $icon;

    /**
     * Couleur de l'axe.
     */
    public AxisColor $color;

    /**
     * Si l'axe est archivé.
     */
    public bool $archived;

    /**
     * Lit le nom, l'icône, la couleur et l'archivage de l'axe.
     *
     * @param  AnalyticAxis|array<string, mixed>|object  $axis  Axe à représenter.
     * @param  bool  $iconOnly  Si la pastille se réduit à l'icône colorée.
     * @param  bool  $onDark  Si l'icône seule se pose sur un fond toujours sombre (infobulle).
     */
    public function __construct(
        array|object $axis,
        public bool $iconOnly = false,
        public bool $onDark = false,
    ) {
        $axis = $axis instanceof AnalyticAxis ? $axis : (object) $axis;

        $this->name = (string) $axis->name;
        $this->icon = 'lucide-'.(in_array($axis->icon ?? '', AxisIcons::names(), strict: true) ? $axis->icon : AxisIcons::DEFAULT);
        $this->color = $axis->color instanceof AxisColor ? $axis->color : (AxisColor::tryFrom((string) ($axis->color ?? '')) ?? AxisColor::DEFAULT);
        $this->archived = $axis instanceof AnalyticAxis
            ? $axis->trashed()
            : (bool) ($axis->archived ?? (($axis->deleted_at ?? null) !== null));
    }

    /**
     * Vue de la pastille.
     */
    public function render(): View
    {
        return view('components.axis-badge');
    }
}
