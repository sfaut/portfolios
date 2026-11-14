<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Mise en page des pages de l'application : barre de navigation, bandeau facultatif, contenu.
 *
 * La barre et le bandeau suivent la largeur de la page : max-w-7xl par défaut, 120rem pour une
 * page large (wide), comme le Grand livre, dont le contenu reprend alors la même largeur et les
 * mêmes marges (WIDE_CONTAINER).
 */
class AppLayout extends Component
{
    /**
     * Largeur et marges du conteneur d'une page large, partagées par la barre, le bandeau et le
     * contenu.
     */
    public const string WIDE_CONTAINER = 'max-w-[120rem] mx-auto px-4 sm:px-6 lg:px-8';

    /**
     * @param  bool  $wide  Si la page occupe une largeur plus grande que max-w-7xl.
     */
    public function __construct(
        public bool $wide = false,
    ) {
        //
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
