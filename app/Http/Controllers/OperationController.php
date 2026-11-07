<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperationRequest;
use App\Models\Account;
use App\Models\Operation;
use Illuminate\Http\RedirectResponse;

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

    public function edit(Operation $operation)
    {
        return view('operation.edit', [
            'operation' => $operation,
        ]);
    }

    public function update(OperationRequest $request, Operation $operation)
    {
        $data = $request->validated();
        $operation->update($data);

        return to_route('account.show', $operation->account)
            ->with('alert', 'Opération modifiée avec succès.')
            ->with('updated_operation_id', $operation->id);
    }

    public function destroy(Operation $operation)
    {
        $operation->delete();

        return to_route('account.show', $operation->account)
            ->with('alert', 'Opération supprimée avec succès');
    }
}
