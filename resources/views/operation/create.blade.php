<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold">Écriture d'une opération</h2>
            <div class="text-sm text-gray-400">
                ➜ Portfolio
                <a href="{{ route('portfolio.show', $account->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $account->portfolio->name }}</a>
                ➜ Compte
                <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $account->name }}</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations de l'opération</x-slot:title>
            <x-slot:description>Renseignez les informations de la nouvelle opération.</x-slot:description>

            @include('operation.partials.form', [
                'action' => route('operation.store', $account),
                'method' => 'post',
                'button' => 'Enregistrer',
                'abort' => route('account.show', $account),
            ])
        </x-card>

    </div>

</x-app-layout>
