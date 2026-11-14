<?php

namespace App\View\Components;

use App\Support\PortfolioContext;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Bandeau d'une page : fil d'Ariane, titre, description et actions (vue components.page-header).
 *
 * Le fil part toujours de la liste des portfolios, puis passe par le portfolio de la page, qui mène
 * à son tableau de bord, avant les liens que la page donne elle-même, de sa section à sa page
 * parente. La page n'y figure pas, puisque son titre la nomme : le tableau de bord n'a que la liste
 * des portfolios. Seule la liste des portfolios, racine du fil, s'y trouve, en page courante, pour
 * que toute page ait son fil.
 */
class PageHeader extends Component
{
    /**
     * Fil d'Ariane complet, de la liste des portfolios à la page parente : adresse => texte.
     *
     * @var array<string, string>
     */
    public array $trail;

    /**
     * Compose le fil d'Ariane de la page, d'après sa route.
     *
     * @param  array<string, string>  $breadcrumb  Liens propres à la page, de sa section à sa page
     *                                             parente (« Comptes » => liste des comptes), vide sur une page de premier niveau.
     */
    public function __construct(array $breadcrumb = [])
    {
        $route = request()->route();
        $portfolio = PortfolioContext::fromRoute($route)->portfolio;

        $this->trail = [
            route('portfolio.index') => 'Portfolios',
            ...($portfolio === null || $route?->named('portfolio.dashboard')
                ? []
                : [route('portfolio.dashboard', $portfolio) => $portfolio->name]),
            ...$breadcrumb,
        ];
    }

    /**
     * Vue du bandeau.
     */
    public function render(): View
    {
        return view('components.page-header');
    }
}
