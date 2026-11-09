<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * En-tête de colonne triable d'un tableau : un `<th>` qui porte aria-sort, et dont le lien trie le
 * tableau sur sa colonne.
 *
 * Le tri se lit dans l'adresse (paramètres sort et dir, que le contrôleur valide). Le lien garde les
 * autres paramètres de l'adresse courante (filtres), revient à la première page, trie sur la colonne
 * dans le sens croissant, ou inverse le sens si le tableau est déjà trié sur elle. Sans JavaScript,
 * le lien recharge la page ; une page peut aussi l'intercepter (attribut data-fragment-link) pour ne
 * recharger que ses résultats.
 */
class SortableHeading extends Component
{
    /**
     * Si le tableau est trié sur cette colonne.
     */
    public bool $active;

    /**
     * Valeur d'aria-sort : ascending, descending, ou none hors de la colonne triée.
     */
    public string $ariaSort;

    /**
     * Icône Lucide de l'état du tri.
     */
    public string $icon;

    /**
     * Adresse qui trie sur la colonne, ou en inverse le sens.
     */
    public string $url;

    /**
     * Ce que fait le lien, pour les lecteurs d'écran.
     */
    public string $hint;

    /**
     * Déduit l'état de la colonne du tri en cours, et l'adresse du tri suivant.
     *
     * @param  string  $column  Nom de la colonne, valeur du paramètre sort.
     * @param  string  $sort  Colonne du tri en cours.
     * @param  string  $direction  Sens du tri en cours : asc ou desc.
     * @param  string  $align  Alignement du contenu : left, center ou right.
     */
    public function __construct(
        public string $column,
        public string $sort,
        public string $direction,
        public string $align = 'center',
    ) {
        $this->active = $column === $sort;

        $next = $this->active && $direction === 'asc' ? 'desc' : 'asc';

        $this->ariaSort = match (true) {
            ! $this->active => 'none',
            $direction === 'asc' => 'ascending',
            default => 'descending',
        };

        $this->icon = match ($this->ariaSort) {
            'ascending' => 'arrow-up',
            'descending' => 'arrow-down',
            default => 'chevrons-up-down',
        };

        $this->hint = $next === 'asc' ? 'trier par ordre croissant' : 'trier par ordre décroissant';
        $this->url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $next, 'page' => null]);
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.sortable-heading');
    }
}
