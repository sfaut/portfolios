<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold">Modification de l'opération <span class="text-indigo-700 dark:text-indigo-400">{{ $operation->id }}</span></h2>
            <x-breadcrumb :links="[route('portfolio.show', $operation->account->portfolio) => 'Comptes', route('account.show', $operation->account) => $operation->account->name]"/>
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
                <a href="{{ route('account.show', $operation->account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-danger-button type="button" commandfor="delete-operation" command="show-modal">Supprimer</x-danger-button>
            </x-slot:footer>
        </x-card>

        <x-dialog id="delete-operation" danger>
            <x-slot:title>Supprimer l'opération « {{ $operation->label }} » ?</x-slot:title>
            <x-slot:description>
                La suppression est définitive : le solde du compte sera recalculé sans cette opération.
            </x-slot:description>

            <x-slot:footer>
                <button type="button" commandfor="delete-operation" command="close" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</button>

                <form method="post" action="{{ route('operation.destroy', $operation) }}">
                    @method('delete')
                    @csrf

                    <x-danger-button>Supprimer l'opération</x-danger-button>
                </form>
            </x-slot:footer>
        </x-dialog>

    </div>

</x-app-layout>
