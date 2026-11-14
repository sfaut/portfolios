<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioRequest;
use App\Models\Operation;
use App\Models\Portfolio;
use App\Support\MonthRestriction;
use App\Support\SalesDashboard;
use App\Support\SalesFigures;
use App\View\Components\MonthPicker;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PortfolioController extends Controller
{
    public function index()
    {
        $portfolios = auth()->user()->portfolios()->withCount('accounts')->get();

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
     * Tableau de bord du portfolio, sa page d'accueil : chiffres clefs à la date du jour, puis ventes
     * de la période choisie (paramètre period, plage de mois « period[start] » / « period[end] »).
     *
     * La période se choisit entre le premier et le dernier mois où le portfolio a des opérations ; une
     * période hors de ces bornes, incomplète ou mal formée est ignorée, sans erreur, comme il convient
     * à un filtre. Par défaut, elle va de M − 13 à M, M étant le mois en cours, ou celui de la dernière
     * opération s'il est antérieur (sans remonter avant la première opération).
     *
     * @param  Request  $request  Requête, avec la période éventuelle.
     * @param  Portfolio  $portfolio  Portfolio affiché.
     */
    public function dashboard(Request $request, Portfolio $portfolio): View
    {
        $today = CarbonImmutable::today();
        $months = $portfolio->operationMonths();
        $restriction = new MonthRestriction(min: $months === [] ? '' : end($months), max: $months[0] ?? '');
        $period = $this->dashboardPeriod($request->query('period'), $months, $restriction, $today);
        $figures = new SalesFigures($portfolio, $today);
        $dashboard = $period === null ? null : new SalesDashboard($portfolio, $period['start'], $period['end'], $today);

        return view('portfolio.dashboard', [
            'portfolio' => $portfolio,
            'today' => $today,
            'active_accounts' => $figures->activeAccountCount(),
            'archived_accounts' => $figures->archivedAccountCount(),
            'current_month' => $figures->currentMonth(),
            'previous_month' => $figures->previousMonth(),
            'last_twelve_months' => $figures->lastTwelveMonths(),
            'outstanding' => $figures->outstanding(),
            'restriction' => $restriction,
            'period' => $period,
            'dashboard' => $dashboard,
            'comparison' => $dashboard?->comparison(),
            'months' => $dashboard?->monthly() ?? [],
            'axes' => $dashboard?->byAnalyticAxis() ?? [],
        ]);
    }

    /**
     * Affiche le portfolio, ses comptes actifs puis ses comptes archivés, filtrés quand des filtres
     * sont donnés, avec le nombre de comptes trouvés.
     *
     * Deux filtres, cumulables : la recherche (paramètre search) sur le nom des comptes, et le mois
     * (paramètre month, « 2026-11 »), qui garde les comptes ayant au moins une opération dans le mois
     * (date effective, delivery_at).
     * La recherche ne tient compte ni de la casse ni des accents (collation utf8mb4_0900_ai_ci), et
     * prend « % » et « _ » littéralement ; une recherche qui n'est pas un texte (search[]=…) est
     * ignorée. Le mois se choisit parmi ceux où le portfolio a des
     * opérations (liste blanche de la restriction du sélecteur) ; un autre est ignoré, sans erreur,
     * comme il convient à un filtre. Une requête ajax (composant Alpine accountSearch) reçoit le
     * seul fragment accounts de la vue, d'où l'en-tête Vary, qui sépare page et fragment en cache.
     *
     * @param  Request  $request  Requête, avec la recherche et le mois éventuels.
     * @param  Portfolio  $portfolio  Portfolio à afficher.
     */
    public function show(Request $request, Portfolio $portfolio): Response
    {
        $search = $request->query('search');
        $search = is_string($search) ? mb_substr(trim($search), 0, 50) : '';
        $escaped_search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

        $month_restriction = new MonthRestriction(allow: $portfolio->operationMonths());
        $month = $request->query('month');

        if (! $month_restriction->isChoosable($month)) {
            $month = '';
        }

        $filtered = $search !== '' || $month !== '';

        $filter = fn (Builder $query) => $query
            ->when($search !== '', fn (Builder $query) => $query->whereLike('name', "%{$escaped_search}%"))
            ->when($month !== '', fn (Builder $query) => $query->whereIn('id', Operation::query()
                ->select('account_id')
                ->where('delivery_at', '>=', "{$month}-01")
                ->where('delivery_at', '<', Carbon::createFromFormat('!Y-m', $month)->addMonth()->toDateString())));

        $accounts = $portfolio->accountsReport()->with('analyticAxes')->when($filtered, $filter)->get();
        $archived_accounts = $portfolio->accountsReport()->onlyTrashed()->with('analyticAxes')->when($filtered, $filter)->get();

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
                'month' => $month === '' ? '' : Str::lower(MonthPicker::monthLabel($month)),
            ]);
        }

        $view = view('portfolio.show', [
            'portfolio' => $portfolio,
            'search' => $search,
            'month' => $month,
            'month_restriction' => $month_restriction,
            'filtered' => $filtered,
            'summary' => $summary,
            'empty_message' => $empty_message,
            'accounts' => $accounts,
            'archived_accounts' => $archived_accounts,
        ]);

        return response($view->fragmentIf($request->ajax(), 'accounts'))
            ->header('Vary', 'X-Requested-With');
    }

    /**
     * Période du tableau de bord : celle reçue si ses deux bornes sont choisissables (remises dans
     * l'ordre), sinon la période par défaut, de M − 13 à M (voir dashboard()) ; null quand le portfolio
     * n'a aucune opération.
     *
     * @param  mixed  $value  Valeur reçue du paramètre period, de n'importe quel type.
     * @param  list<string>  $months  Mois où le portfolio a des opérations, du plus récent au plus ancien.
     * @param  MonthRestriction  $restriction  Mois choisissables.
     * @param  CarbonImmutable  $today  Date du jour, dont le mois sert de M.
     * @return array{start: string, end: string}|null
     */
    private function dashboardPeriod(mixed $value, array $months, MonthRestriction $restriction, CarbonImmutable $today): ?array
    {
        if ($months === []) {
            return null;
        }

        if (is_array($value) && $restriction->isChoosable($value['start'] ?? null) && $restriction->isChoosable($value['end'] ?? null)) {
            return ['start' => min($value['start'], $value['end']), 'end' => max($value['start'], $value['end'])];
        }

        $end = min($today->format('Y-m'), $months[0]);
        $start = CarbonImmutable::createFromFormat('!Y-m', $end)->subMonthsNoOverflow(13)->format('Y-m');

        return ['start' => max($start, end($months)), 'end' => $end];
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
