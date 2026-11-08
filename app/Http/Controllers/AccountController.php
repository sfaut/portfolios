<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\Account;
use App\Models\Portfolio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        ]);
    }

    public function store(AccountRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $new_account = $request->validated();
        $new_account['portfolio_id'] = $portfolio->id;

        Account::create($new_account);

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
        ]);
    }

    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        $data = $request->validated();
        $account->update($data);

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
