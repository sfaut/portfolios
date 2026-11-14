<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold">Écriture d'une opération</h2>
            <x-breadcrumb :links="[route('portfolio.show', $account->portfolio) => 'Comptes', route('account.show', $account) => $account->name]"/>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations de l'opération</x-slot:title>
            <x-slot:description>Renseignez les informations de la nouvelle opération.</x-slot:description>

            @include('operation.partials.form', [
                'action' => route('operation.store', $account),
                'method' => 'post',
            ])

            <x-slot:footer>
                <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-primary-button form="operation-form" icon="check">Enregistrer</x-primary-button>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
