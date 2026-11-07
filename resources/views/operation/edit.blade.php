<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold">Modification de l'opération <span class="text-indigo-700">{{ $operation->id }}</span></h2>
                <div class="text-sm text-gray-400">
                    ➜ Portfolio
                    <a href="{{ route('portfolio.show', $operation->account->portfolio) }}" class="text-indigo-400 underline hover:no-underline">{{ $operation->account->portfolio->name }}</a>
                    ➜ Compte
                    <a href="{{ route('account.show', $operation->account->id) }}" class="text-indigo-400 underline hover:no-underline">{{ $operation->account->name }}</a>
                </div>
            </div>
            <div class="text-sm flex text-indigo-300 hover:text-indigo-700 transition-colors ease-linear delay-100">
                <a href="{{ route('account.show', $operation->account_id) }}" class="px-2 underline hover:no-underline">Annuler</a>
            </div>
        </div>
    </x-slot>

    @include('operation.partials.form', [
        'action' => route('operation.update', $operation),
        'method' => 'put',
        'button' => 'Modifier',
        'abort' => route('account.show', $operation->account),
    ])

    <div @class([
        'max-w-xl', 'mx-auto',
        'shadow-sm', 'rounded-md',
        'p-6', 'bg-white',
        'ring-2', 'ring-red-800',
        'mt-6',
    ])>
        <header>
            <h2 class="text-lg font-semibold">Suppression de l'opération</h2>
            <p class="mt-1 text-sm text-gray-400">
                La suppression de l'opération est définitive et irréversible.
            </p>
        </header>
        <form method="post" action="{{ route('operation.destroy', $operation) }}" class="space-y-6">
            @method('delete')
            @csrf

            <div class="flex items-center gap-6">
                <x-danger-button class="">
                    Supprimer
                </x-danger-button>
                <a href="{{ route('account.show', $operation->account) }}" class="text-indigo-700 underline hover:no-underline">Annuler</a>
            </div>
        </form>
    </div>

</x-app-layout>
