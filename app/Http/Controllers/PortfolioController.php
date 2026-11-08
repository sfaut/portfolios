<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioRequest;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortfolioController extends Controller
{
    public function index()
    {
        $portfolios = auth()->user()->portfolios()->get();

        return view('portfolio.index', [
            'portfolios' => $portfolios,
        ]);
    }

    public function create()
    {
        return view('portfolio.create');
    }

    public function store(PortfolioRequest $request)
    {
        $data = $request->validated();

        auth()->user()->portfolios()->create($data);

        return to_route('portfolio.index')
            ->with('message', trans('portfolio.store.success', ['name' => $data['name']]));
    }

    /**
     * Affiche le portfolio, ses comptes actifs puis ses comptes archivés, filtrés sur leur nom par la
     * recherche (paramètre search) quand elle est donnée, avec le nombre de comptes trouvés.
     *
     * La recherche ne tient compte ni de la casse ni des accents (collation utf8mb4_0900_ai_ci), et
     * prend « % » et « _ » littéralement. Une requête ajax (composant Alpine accountSearch) reçoit le
     * seul fragment accounts de la vue, d'où l'en-tête Vary, qui sépare page et fragment en cache.
     *
     * @param  Request  $request  Requête, avec la recherche éventuelle.
     * @param  Portfolio  $portfolio  Portfolio à afficher.
     */
    public function show(Request $request, Portfolio $portfolio): Response
    {
        $search = mb_substr($request->string('search')->trim()->value(), 0, 50);

        $escaped_search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

        $filter = fn (Builder $query) => $query->whereLike('name', "%{$escaped_search}%");

        $accounts = $portfolio->accountsReport()->when($search !== '', $filter)->get();
        $archived_accounts = $portfolio->accountsReport()->onlyTrashed()->when($search !== '', $filter)->get();

        $summary = '';

        if ($search !== '') {
            $summary = trans_choice('portfolio.search.summary', $accounts->count() + $archived_accounts->count(), [
                'total' => $portfolio->accountsReport()->withTrashed()->count(),
            ]);
        }

        $view = view('portfolio.show', [
            'portfolio' => $portfolio,
            'search' => $search,
            'summary' => $summary,
            'accounts' => $accounts,
            'archived_accounts' => $archived_accounts,
        ]);

        return response($view->fragmentIf($request->ajax(), 'accounts'))
            ->header('Vary', 'X-Requested-With');
    }

    public function edit(Portfolio $portfolio)
    {
        return view('portfolio.edit', [
            'portfolio' => $portfolio,
        ]);
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio)
    {
        $data = $request->validated();

        $portfolio->update($data);

        return to_route('portfolio.show', $portfolio)
            ->with('message', trans('portfolio.update.success', ['name' => $portfolio->name]));
    }

    public function destroy(Portfolio $portfolio)
    {
        $portfolio->delete();

        return to_route('portfolio.index')
            ->with('message', trans('portfolio.destroy.success', ['name' => $portfolio->name]));
    }
}
