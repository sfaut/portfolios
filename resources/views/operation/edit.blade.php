<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold">Modification de l'opération <span class="text-indigo-700 dark:text-indigo-400">{{ $operation->id }}</span></h2>
                <div class="text-sm text-gray-400">
                    ➜ Portfolio
                    <a href="{{ route('portfolio.show', $operation->account->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $operation->account->portfolio->name }}</a>
                    ➜ Compte
                    <a href="{{ route('account.show', $operation->account->id) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $operation->account->name }}</a>
                </div>
            </div>
            <div class="text-sm flex text-indigo-300 hover:text-indigo-700 dark:text-indigo-500 dark:hover:text-indigo-300 transition-colors ease-linear delay-100">
                <a href="{{ route('account.show', $operation->account_id) }}" class="px-2 underline hover:no-underline">Annuler</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations de l'opération</x-slot:title>
            <x-slot:description>Modifiez les informations de l'opération.</x-slot:description>

            @include('operation.partials.form', [
                'action' => route('operation.update', $operation),
                'method' => 'put',
                'button' => 'Modifier',
                'abort' => route('account.show', $operation->account),
            ])
        </x-card>

        <x-card danger>
            <x-slot:title>Suppression de l'opération</x-slot:title>
            <x-slot:description>
                La suppression de l'opération est définitive et irréversible.
            </x-slot:description>

            <form method="post" action="{{ route('operation.destroy', $operation) }}" class="flex items-center gap-6">
                @method('delete')
                @csrf

                <x-danger-button>
                    Supprimer
                </x-danger-button>
                <a href="{{ route('account.show', $operation->account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
            </form>
        </x-card>

    </div>

</x-app-layout>
