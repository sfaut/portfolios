<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperationRequest;
use App\Http\Requests\OperationTickRequest;
use App\Models\Account;
use App\Models\AnalyticAxis;
use App\Models\AnalyticLine;
use App\Models\Operation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperationController extends Controller
{
    /**
     * Affiche le formulaire d'écriture d'une opération sur le compte.
     *
     * @param  Account  $account  Compte non archivé qui recevra l'opération.
     */
    public function create(Account $account): View
    {
        return view('operation.create', [
            'account' => $account,
            'analytic_form' => $this->analyticForm($account),
        ]);
    }

    /**
     * Enregistre une nouvelle opération sur le compte, avec sa répartition analytique, puis revient
     * au compte.
     *
     * @param  OperationRequest  $request  Requête validée (libellé, montant, date effective, lignes analytiques à 100 %).
     * @param  Account  $account  Compte qui reçoit l'opération.
     */
    public function store(OperationRequest $request, Account $account): RedirectResponse
    {
        DB::transaction(function () use ($request, $account): void {
            $operation = $account->operations()->create($request->safe()->except('analytic_lines'));
            $operation->analyticLines()->createMany($request->analyticLines());
        });

        return to_route('account.show', $account)
            ->with('alert', 'Opération ajoutée avec succès.');
    }

    /**
     * Affiche le formulaire de modification de l'opération.
     *
     * @param  Operation  $operation  Opération à modifier, d'un compte non archivé (404 sinon).
     */
    public function edit(Operation $operation): View
    {
        $this->abortIfAccountArchived($operation);

        return view('operation.edit', [
            'operation' => $operation,
            'analytic_form' => $this->analyticForm($operation->account, $operation),
        ]);
    }

    /**
     * Enregistre les modifications de l'opération, dont sa répartition analytique, qui remplace la
     * précédente, puis revient au compte.
     *
     * @param  OperationRequest  $request  Requête validée (libellé, montant, date effective, lignes analytiques à 100 %).
     * @param  Operation  $operation  Opération à modifier, d'un compte non archivé (404 sinon).
     */
    public function update(OperationRequest $request, Operation $operation): RedirectResponse
    {
        $this->abortIfAccountArchived($operation);

        DB::transaction(function () use ($request, $operation): void {
            $operation->update($request->safe()->except('analytic_lines'));
            $operation->analyticLines()->delete();
            $operation->analyticLines()->createMany($request->analyticLines());
        });

        return to_route('account.show', $operation->account)
            ->with('alert', 'Opération modifiée avec succès.')
            ->with('updated_operation_id', $operation->id);
    }

    /**
     * Pointe ou dépointe l'opération, sans toucher à ses autres champs.
     *
     * La case à cocher l'envoie par script et reçoit en JSON l'état enregistré et le message de son
     * toast ; sans script, le formulaire revient à sa page, avec ce message en flash `toast`.
     *
     * @param  OperationTickRequest  $request  État voulu (is_ticked).
     * @param  Operation  $operation  Opération à pointer, d'un compte non archivé (404 sinon).
     */
    public function tick(OperationTickRequest $request, Operation $operation): JsonResponse|RedirectResponse
    {
        $this->abortIfAccountArchived($operation);

        $operation->is_ticked = $request->boolean('is_ticked');
        $operation->save();

        $message = $operation->is_ticked
            ? "« {$operation->label} » pointée"
            : "« {$operation->label} » dépointée";

        if ($request->expectsJson()) {
            return response()->json([
                'is_ticked' => $operation->is_ticked,
                'message' => $message,
            ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => $message]);
    }

    /**
     * Supprime l'opération, puis revient au compte.
     *
     * @param  Operation  $operation  Opération à supprimer, d'un compte non archivé (404 sinon).
     */
    public function destroy(Operation $operation): RedirectResponse
    {
        $this->abortIfAccountArchived($operation);

        $operation->delete();

        return to_route('account.show', $operation->account)
            ->with('alert', 'Opération supprimée avec succès');
    }

    /**
     * Interrompt la requête par une 404 quand le compte de l'opération est archivé : ses opérations
     * sont alors en lecture seule, comme la création, dont la liaison de modèle écarte les comptes
     * archivés.
     *
     * @param  Operation  $operation  Opération dont le compte est contrôlé.
     */
    private function abortIfAccountArchived(Operation $operation): void
    {
        abort_if($operation->account->trashed(), 404);
    }

    /**
     * Données de la répartition analytique du formulaire (resources/js/analytic-lines.js) : axes
     * proposés, avec leur icône colorée rendue en SVG, et lignes initiales. Après une validation échouée, les lignes envoyées reviennent,
     * même aucune ; sinon, celles de l'opération.
     *
     * @param  Account  $account  Compte de l'opération.
     * @param  Operation|null  $operation  Opération modifiée, null en création.
     * @return array{
     *     axes: list<array{id: int, name: string, archived: bool, icon: string}>,
     *     lines: list<array{analytic_axis_id: string, percentage: string}>,
     * }
     */
    private function analyticForm(Account $account, ?Operation $operation = null): array
    {
        $lines = session()->hasOldInput()
            ? array_values((array) old('analytic_lines', []))
            : $operation?->analyticLines
                ->map(fn (AnalyticLine $line): array => [
                    'analytic_axis_id' => (string) $line->analytic_axis_id,
                    'percentage' => $line->percentage,
                ])
                ->all() ?? [];

        return [
            'axes' => $this->selectableAnalyticAxes($account, $operation)
                ->map(fn (AnalyticAxis $axis): array => $axis->formOption())
                ->values()
                ->all(),
            'lines' => $lines,
        ];
    }

    /**
     * Axes analytiques proposés par le formulaire : ceux du portfolio du compte, non archivés, plus
     * les axes archivés que l'opération utilise déjà.
     *
     * @param  Account  $account  Compte de l'opération.
     * @param  Operation|null  $operation  Opération modifiée, null en création.
     * @return Collection<int, AnalyticAxis>
     */
    private function selectableAnalyticAxes(Account $account, ?Operation $operation = null): Collection
    {
        $used_axis_ids = $operation?->analyticLines->pluck('analytic_axis_id')->all() ?? [];

        return $account->portfolio->analyticAxes()
            ->withTrashed()
            ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhereIn('id', $used_axis_ids))
            ->get();
    }
}
