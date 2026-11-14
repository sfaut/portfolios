<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Mise en page des pages invité (connexion, inscription, mot de passe, vérification, pages
 * d'erreur) : un écran partagé, décrit dans layouts/guest.blade.php, qui reçoit les slots
 * title, description et footer.
 */
class GuestLayout extends Component
{
    /**
     * Rend la vue de la mise en page invité.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
