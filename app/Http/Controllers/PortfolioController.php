<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioRequest;
use App\Models\Operation;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
     * Affiche le portfolio, ses comptes actifs puis ses comptes archivés, filtrés quand des filtres
     * sont donnés, avec le nombre de comptes trouvés.
     *
     * Deux filtres, cumulables : la recherche (paramètre search) sur le nom des comptes, et le mois
     * (paramètre month, « 2026-11 »), qui garde les comptes ayant au moins une opération dans le mois.
     * La recherche ne tient compte ni de la casse ni des accents (collation utf8mb4_0900_ai_ci), et
     * prend « % » et « _ » littéralement. Le mois se choisit parmi ceux où le portfolio a des
     * opérations ; un autre est ignoré. Une requête ajax (composant Alpine accountSearch) reçoit le
     * seul fragment accounts de la vue, d'où l'en-tête Vary, qui sépare page et fragment en cache.
     *
     * @param  Request  $request  Requête, avec la recherche et le mois éventuels.
     * @param  Portfolio  $portfolio  Portfolio à afficher.
     */
    public function show(Request $request, Portfolio $portfolio): Response
    {
        $search = mb_substr($request->string('search')->trim()->value(), 0, 50);
        $escaped_search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

        $months = $portfolio->operationMonths();
        $month = $request->string('month')->value();

        if (! array_key_exists($month, $months)) {
            $month = '';
        }

        $filtered = $search !== '' || $month !== '';

        $filter = fn (Builder $query) => $query
            ->when($search !== '', fn (Builder $query) => $query->whereLike('name', "%{$escaped_search}%"))
            ->when($month !== '', fn (Builder $query) => $query->whereIn('id', Operation::query()
                ->select('account_id')
                ->where('delivery_at', '>=', "{$month}-01")
                ->where('delivery_at', '<', Carbon::createFromFormat('!Y-m', $month)->addMonth()->toDateString())));

        $accounts = $portfolio->accountsReport()->when($filtered, $filter)->get();
        $archived_accounts = $portfolio->accountsReport()->onlyTrashed()->when($filtered, $filter)->get();

        $summary = '';
        $empty_message = '';

        if ($filtered) {
            $summary = trans_choice('portfolio.search.summary', $accounts->count() + $archived_accounts->count(), [
                'total' => $portfolio->accountsReport()->withTrashed()->count(),
            ]);

            $empty_message = trans(match (true) {
                $month === '' => 'portfolio.search.empty.name',
                $search === '' => 'portfolio.search.empty.month',
                default => 'portfolio.search.empty.both',
            }, [
                'search' => $search,
                'month' => Str::lower($months[$month] ?? ''),
            ]);
        }

        $view = view('portfolio.show', [
            'portfolio' => $portfolio,
            'search' => $search,
            'month' => $month,
            'months' => $months,
            'filtered' => $filtered,
            'summary' => $summary,
            'empty_message' => $empty_message,
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
