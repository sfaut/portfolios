<?php

namespace App\Http\Controllers;

use App\Models\AnalyticLine;
use App\Models\Operation;
use App\Models\Portfolio;
use App\Support\Csv;
use App\Support\Ledger\Ledger;
use App\Support\Ledger\LedgerFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Grand livre d'un portfolio : toutes ses opérations, filtrées et triées (App\Support\Ledger).
 */
class LedgerController extends Controller
{
    /**
     * Clef de session de la préférence « Tout » : toutes les opérations du grand livre, sans
     * pagination.
     */
    public const string SHOW_ALL_OPERATIONS = 'ledger_show_all_operations';

    /**
     * Affiche le grand livre, paginé ou entier.
     *
     * Le bouton poussoir « Tout » envoie all=1 ou all=0 avec les filtres : la préférence est gardée
     * en session, pour tous les portfolios, puis la page se recharge sans le paramètre, filtres et
     * tri conservés. Une requête ajax ne reçoit que le fragment « ledger » (totaux, outils et
     * tableau), qui remplace les résultats de la page.
     *
     * @param  Request  $request  Requête, avec les filtres, le tri, la page ou la préférence all éventuels.
     * @param  Portfolio  $portfolio  Portfolio dont on affiche le grand livre.
     */
    public function index(Request $request, Portfolio $portfolio): Response|RedirectResponse
    {
        $ledger = new Ledger($portfolio);
        $filters = LedgerFilters::fromRequest($request, $ledger);

        if ($request->has('all')) {
            $request->session()->put(self::SHOW_ALL_OPERATIONS, $request->boolean('all'));

            return to_route('ledger.index', ['portfolio' => $portfolio, ...$filters->query()]);
        }

        $show_all = $request->session()->get(self::SHOW_ALL_OPERATIONS, false) === true;

        $view = view('ledger.index', [
            'portfolio' => $portfolio,
            'filters' => $filters,
            'operations' => $show_all ? $ledger->all($filters) : $ledger->paginate($filters),
            'show_all' => $show_all,
            'totals' => $ledger->totals($filters),
            'operation_count' => $ledger->count(),
            'account_options' => $ledger->accountOptions(),
            'axis_options' => $ledger->axisOptions(),
            'written_restriction' => $ledger->writtenRestriction(),
            'effective_restriction' => $ledger->effectiveRestriction(),
        ]);

        return response($view->fragmentIf($request->ajax(), 'ledger'))
            ->header('Vary', 'X-Requested-With');
    }

    /**
     * Télécharge le grand livre en CSV (App\Support\Csv), avec les filtres et le tri de la requête,
     * sans pagination. Le fichier est écrit au fil de la lecture des opérations.
     *
     * @param  Request  $request  Requête, avec les filtres et le tri éventuels.
     * @param  Portfolio  $portfolio  Portfolio dont on exporte le grand livre.
     */
    public function export(Request $request, Portfolio $portfolio): StreamedResponse
    {
        $ledger = new Ledger($portfolio);
        $filters = LedgerFilters::fromRequest($request, $ledger);

        $rows = $ledger->rows($filters)->map(fn (Operation $operation): array => [
            $operation->id,
            $operation->account_id,
            Csv::text($operation->account->name),
            Csv::dateTime($operation->created_at),
            $operation->delivery_at,
            Csv::text($operation->label),
            Csv::decimal($operation->amount),
            Csv::text($operation->analyticLines
                ->sortBy(fn (AnalyticLine $line): string => $line->analyticAxis->name, SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn (AnalyticLine $line): string => "{$line->analyticAxis->name} ".Number::percentage($line->percentage, maxPrecision: 2, locale: 'fr'))
                ->implode(' | ')),
        ]);

        $name = 'grand-livre-'.Str::slug($portfolio->name).'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');

            Csv::write($stream, ['id', 'compte_id', 'compte', 'horodatage écriture', 'date effective', 'libellé', 'montant', 'axes'], $rows);

            fclose($stream);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
