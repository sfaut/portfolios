<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold">Écriture d'une opération</h2>
                <div class="text-sm text-gray-400">
                    ➜ Portfolio
                    <a href="{{ route('portfolio.show', $account->portfolio) }}" class="text-indigo-400 underline hover:no-underline">{{ $account->portfolio->name }}</a>
                    ➜ Compte
                    <a href="{{ route('account.show', $account) }}" class="text-indigo-400 underline hover:no-underline">{{ $account->name }}</a>
                </div>
            </div>
            <div class="text-sm flex text-indigo-300 hover:text-indigo-700 dark:text-indigo-500 dark:hover:text-indigo-300 transition-colors ease-linear delay-100">
                <a href="{{ route('account.show', $account->id) }}" class="px-2 underline hover:no-underline">Annuler</a>
            </div>
        </div>
    </x-slot>

    @include('operation.partials.form', [
        'action' => route('operation.store', $account),
        'method' => 'post',
        'button' => 'Enregistrer',
        'abort' => route('account.show', $account),
    ])

</x-app-layout>
