<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperationRequest;
use App\Models\Account;
use App\Models\Operation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OperationController extends Controller
{
    public function create(Account $account)
    {
        return view('operation.create', [
            'account' => $account,
        ]);
    }

    /**
     * Enregistre une nouvelle opération sur le compte, puis revient au compte.
     *
     * @param  OperationRequest  $request  Requête validée (libellé, montant, date effective).
     * @param  Account  $account  Compte qui reçoit l'opération.
     */
    public function store(OperationRequest $request, Account $account): RedirectResponse
    {
        $new_operation = $request->validated();

        $account->operations()->create($new_operation);

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
        ]);
    }

    /**
     * Enregistre les modifications de l'opération, puis revient au compte.
     *
     * @param  OperationRequest  $request  Requête validée (libellé, montant, date effective).
     * @param  Operation  $operation  Opération à modifier, d'un compte non archivé (404 sinon).
     */
    public function update(OperationRequest $request, Operation $operation): RedirectResponse
    {
        $this->abortIfAccountArchived($operation);

        $data = $request->validated();
        $operation->update($data);

        return to_route('account.show', $operation->account)
            ->with('alert', 'Opération modifiée avec succès.')
            ->with('updated_operation_id', $operation->id);
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
}
