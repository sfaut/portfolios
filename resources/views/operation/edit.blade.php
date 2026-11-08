<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold">Modification de l'opération <span class="text-indigo-700 dark:text-indigo-400">{{ $operation->id }}</span></h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                ➜ Portfolio
                <a href="{{ route('portfolio.show', $operation->account->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $operation->account->portfolio->name }}</a>
                ➜ Compte
                <a href="{{ route('account.show', $operation->account->id) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $operation->account->name }}</a>
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
            ])

            <x-slot:footer>
                <a href="{{ route('account.show', $operation->account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-primary-button form="operation-form">Modifier</x-primary-button>
            </x-slot:footer>
        </x-card>

        <x-card danger>
            <x-slot:title>Suppression de l'opération</x-slot:title>
            <x-slot:description>
                La suppression de l'opération est définitive et irréversible.
            </x-slot:description>

            <x-slot:footer>
                <form method="post" action="{{ route('operation.destroy', $operation) }}" class="flex items-center gap-6">
                    @method('delete')
                    @csrf

                    <a href="{{ route('account.show', $operation->account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                    <x-danger-button>Supprimer</x-danger-button>
                </form>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
