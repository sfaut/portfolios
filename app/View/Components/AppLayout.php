<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Mise en page des pages de l'application : barre de navigation, bandeau facultatif, contenu.
 *
 * Barre, bandeau et contenu partagent un même conteneur, donc la même largeur et les mêmes marges :
 * CONTAINER (max-w-7xl) par défaut, WIDE_CONTAINER (120rem) pour une page large (wide), comme le
 * Grand livre. Une page range son contenu dans ce conteneur sans redéclarer de largeur, sauf un
 * formulaire, qui garde une largeur de lecture (max-w-2xl), aligné à gauche.
 */
class AppLayout extends Component
{
    /**
     * Largeur et marges du conteneur d'une page, partagées par la barre, le bandeau et le contenu.
     */
    public const string CONTAINER = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';

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

    /**
     * Classes du conteneur de la page : WIDE_CONTAINER pour une page large, CONTAINER sinon.
     */
    public function container(): string
    {
        return $this->wide ? self::WIDE_CONTAINER : self::CONTAINER;
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
