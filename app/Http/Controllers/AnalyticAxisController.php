<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyticAxisRequest;
use App\Models\AnalyticAxis;
use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestion des axes analytiques d'un portfolio : liste, création, modification, archivage et
 * désarchivage.
 */
class AnalyticAxisController extends Controller
{
    /**
     * Affiche les axes du portfolio, actifs puis archivés, avec leur nombre de lignes analytiques et
     * le nombre de comptes actifs qui les ont dans leur répartition par défaut.
     *
     * @param  Portfolio  $portfolio  Portfolio des axes.
     */
    public function index(Portfolio $portfolio): View
    {
        return view('analytic-axis.index', [
            'portfolio' => $portfolio,
            'analytic_axes' => $portfolio->analyticAxes()->withCount(['analyticLines', 'accounts'])->get(),
            'archived_analytic_axes' => $portfolio->analyticAxes()->onlyTrashed()->withCount(['analyticLines', 'accounts'])->get(),
        ]);
    }

    /**
     * Affiche le formulaire de création d'un axe.
     *
     * @param  Portfolio  $portfolio  Portfolio qui recevra l'axe.
     */
    public function create(Portfolio $portfolio): View
    {
        return view('analytic-axis.create', [
            'portfolio' => $portfolio,
        ]);
    }

    /**
     * Enregistre un nouvel axe dans le portfolio, puis revient à la liste des axes.
     *
     * @param  AnalyticAxisRequest  $request  Requête validée (nom, description).
     * @param  Portfolio  $portfolio  Portfolio qui reçoit l'axe.
     */
    public function store(AnalyticAxisRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $analytic_axis = $portfolio->analyticAxes()->create($request->validated());

        return to_route('analytic-axis.index', $portfolio)
            ->with('message', "L'axe analytique « {$analytic_axis->name} » a été créé.");
    }

    /**
     * Affiche le formulaire de modification de l'axe, avec son archivage ou son désarchivage.
     *
     * @param  AnalyticAxis  $analytic_axis  Axe à modifier, archivé ou non.
     */
    public function edit(AnalyticAxis $analytic_axis): View
    {
        return view('analytic-axis.edit', [
            'analytic_axis' => $analytic_axis,
        ]);
    }

    /**
     * Enregistre les modifications de l'axe, puis revient à la liste des axes.
     *
     * @param  AnalyticAxisRequest  $request  Requête validée (nom, description).
     * @param  AnalyticAxis  $analytic_axis  Axe à modifier, archivé ou non.
     */
    public function update(AnalyticAxisRequest $request, AnalyticAxis $analytic_axis): RedirectResponse
    {
        $analytic_axis->update($request->validated());

        return to_route('analytic-axis.index', $analytic_axis->portfolio)
            ->with('message', "Les modifications de l'axe analytique « {$analytic_axis->name} » ont été enregistrées.");
    }

    /**
     * Désarchive l'axe, puis revient à la liste des axes.
     *
     * @param  AnalyticAxis  $analytic_axis  Axe archivé à désarchiver.
     */
    public function restore(AnalyticAxis $analytic_axis): RedirectResponse
    {
        $analytic_axis->restore();

        return to_route('analytic-axis.index', $analytic_axis->portfolio)
            ->with('message', "L'axe analytique « {$analytic_axis->name} » a été désarchivé.");
    }

    /**
     * Archive l'axe, puis revient à la liste des axes. Ses lignes analytiques restent en place.
     *
     * @param  AnalyticAxis  $analytic_axis  Axe à archiver.
     */
    public function destroy(AnalyticAxis $analytic_axis): RedirectResponse
    {
        $analytic_axis->delete();

        return to_route('analytic-axis.index', $analytic_axis->portfolio)
            ->with('message', "L'axe analytique « {$analytic_axis->name} » a été archivé.");
    }
}
