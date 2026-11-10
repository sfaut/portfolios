<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\Account;
use App\Models\Portfolio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index()
    {
        // portfolio.show
    }

    public function create(Portfolio $portfolio)
    {
        return view('account.create', [
            'portfolio' => $portfolio,
            'analytic_axes' => $portfolio->assignableAnalyticAxes(),
            'axes_index_url' => route('analytic-axis.index', $portfolio),
            'checked_axis_ids' => $this->checkedAxisIds(),
        ]);
    }

    /**
     * Crée le compte dans le portfolio, avec ses axes analytiques.
     *
     * @param  AccountRequest  $request  Données validées du compte.
     * @param  Portfolio  $portfolio  Portfolio du compte.
     */
    public function store(AccountRequest $request, Portfolio $portfolio): RedirectResponse
    {
        DB::transaction(function () use ($request, $portfolio): void {
            $account = Account::create([
                ...$request->safe()->except('analytic_axes'),
                'portfolio_id' => $portfolio->id,
            ]);

            $account->analyticAxes()->sync($request->validated('analytic_axes', []));
        });

        return to_route('portfolio.show', $portfolio)
            ->with(['message' => 'Le compte a bien été ajouté au portfolio.']);
    }

    /**
     * Clef de session de la préférence « Tout » : toutes les opérations d'un compte, sans pagination.
     */
    public const string SHOW_ALL_OPERATIONS = 'show_all_operations';

    /**
     * Affiche un compte et ses opérations, paginées par 20 ou toutes à la fois.
     *
     * Le bouton poussoir « Tout » envoie all=1 ou all=0 : la préférence est gardée en session, pour
     * tous les comptes, puis la page se recharge sans le paramètre. Paginée, la page s'ouvre par
     * défaut sur la dernière page, celle des opérations les plus récentes. Le flash de session est
     * refait à chaque redirection, pour garder le message.
     *
     * @param  Request  $request  Requête, avec la page ou la préférence all éventuelles.
     * @param  Account  $account  Compte à afficher, archivé ou non.
     */
    public function show(Request $request, Account $account): View|RedirectResponse
    {
        if ($request->has('all')) {
            $request->session()->put(self::SHOW_ALL_OPERATIONS, $request->boolean('all'));
            $request->session()->reflash();

            return to_route('account.show', $account);
        }

        $show_all = $request->session()->get(self::SHOW_ALL_OPERATIONS, false) === true;
        $query = $account->operationsReport()->with('analyticLines.analyticAxis');

        if ($show_all) {
            return view('account.show', [
                'account' => $account,
                'operations' => $query->get(),
                'show_all' => true,
            ]);
        }

        $operations = $query->paginate(20);

        if (! $request->has('page')) {
            $request->session()->reflash();

            return to_route('account.show', [
                'account' => $account,
                'page' => $operations->lastPage(),
            ]);
        }

        return view('account.show', [
            'account' => $account,
            'operations' => $operations,
            'show_all' => false,
        ]);
    }

    public function edit(Account $account)
    {
        return view('account.edit', [
            'account' => $account,
            'analytic_axes' => $account->portfolio->assignableAnalyticAxes($account),
            'axes_index_url' => route('analytic-axis.index', $account->portfolio),
            'checked_axis_ids' => $this->checkedAxisIds($account),
        ]);
    }

    /**
     * Axes cochés dans le formulaire : après une validation refusée, ceux envoyés, même aucun ;
     * sinon, ceux du compte modifié, aucun en création.
     *
     * @param  Account|null  $account  Compte modifié, null en création.
     * @return list<int>
     */
    private function checkedAxisIds(?Account $account = null): array
    {
        if (session()->hasOldInput()) {
            $old = old('analytic_axes', []);

            return array_values(array_map(intval(...), array_filter(is_array($old) ? $old : [], is_numeric(...))));
        }

        return $account?->analyticAxes()->pluck('analytic_axes.id')->all() ?? [];
    }

    /**
     * Modifie le compte et remplace ses axes analytiques par ceux cochés, aucun s'il n'y en a pas.
     *
     * @param  AccountRequest  $request  Données validées du compte.
     * @param  Account  $account  Compte modifié, archivé ou non.
     */
    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        DB::transaction(function () use ($request, $account): void {
            $account->update($request->safe()->except('analytic_axes'));
            $account->analyticAxes()->sync($request->validated('analytic_axes', []));
        });

        return to_route('account.show', ['account' => $account])
            ->with('alert', 'Les modifications sur le compte ont été enregistrées.');
    }

    /**
     * Désarchive le compte, puis revient au compte.
     *
     * @param  Account  $account  Compte archivé à désarchiver.
     */
    public function restore(Account $account): RedirectResponse
    {
        $account->restore();

        return to_route('account.show', ['account' => $account])
            ->with('alert', "Le compte « {$account->name} » a été désarchivé.");
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return to_route('portfolio.show', ['portfolio' => $account->portfolio])
            ->with('message', "Le compte « {$account->name} » a été archivé avec succès.");
    }
}
