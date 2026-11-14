<?php

namespace App\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Document HTML de toutes les pages (layouts/empty.blade.php), sous les mises en page de
 * l'application et des pages invité.
 */
class EmptyLayout extends Component
{
    /**
     * @param  Htmlable|string  $title  Titre de la page, écrit dans l'onglet devant le nom de l'application ; vide pour le nom seul.
     */
    public function __construct(
        public Htmlable|string $title = '',
    ) {
        //
    }

    /**
     * Rend la vue du document HTML.
     */
    public function render(): View
    {
        return view('layouts.empty');
    }
}
