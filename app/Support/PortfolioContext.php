<?php

namespace App\Support;

use App\Models\Account;
use App\Models\AnalyticAxis;
use App\Models\Operation;
use App\Models\Portfolio;
use Illuminate\Routing\Route;

/**
 * Portfolio de la page affichée et section de ce portfolio, pour la barre de navigation.
 *
 * Le portfolio se déduit du modèle lié par la route : le portfolio lui-même, ou le parent d'un compte,
 * d'une opération ou d'un axe analytique. La section est celle de la navigation du portfolio
 * (<x-portfolio-nav>) : tableau de bord, comptes (liste des comptes, un compte, une opération), grand
 * livre ou axes analytiques ; aucune hors de ces pages (modification du portfolio).
 */
final readonly class PortfolioContext
{
    /**
     * @param  Portfolio|null  $portfolio  Portfolio de la page, null hors d'un portfolio.
     * @param  string  $section  Section courante : dashboard, accounts, ledger, analytic-axes, ou '' sans section.
     */
    public function __construct(
        public ?Portfolio $portfolio,
        public string $section,
    ) {
        //
    }

    /**
     * Contexte d'une route : son portfolio, retrouvé par le modèle lié, et sa section.
     *
     * @param  Route|null  $route  Route courante, null hors d'une requête routée.
     */
    public static function fromRoute(?Route $route): self
    {
        $portfolio = match (true) {
            $route === null => null,
            $route->parameter('portfolio') instanceof Portfolio => $route->parameter('portfolio'),
            $route->parameter('account') instanceof Account => $route->parameter('account')->portfolio,
            $route->parameter('operation') instanceof Operation => $route->parameter('operation')->account->portfolio,
            $route->parameter('analytic_axis') instanceof AnalyticAxis => $route->parameter('analytic_axis')->portfolio,
            default => null,
        };

        $section = match (true) {
            $portfolio === null => '',
            $route->named('portfolio.dashboard') => 'dashboard',
            $route->named('portfolio.show', 'account.*', 'operation.*') => 'accounts',
            $route->named('ledger.*') => 'ledger',
            $route->named('analytic-axis.*') => 'analytic-axes',
            default => '',
        };

        return new self($portfolio, $section);
    }
}
