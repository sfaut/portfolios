<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold">Compte <span class="text-indigo-700 dark:text-indigo-400">{{ $account->name }}</span></h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                ➜ Portfolio
                <a href="{{ route('portfolio.show', $account->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $account->portfolio->name }}</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations du compte</x-slot:title>
            <x-slot:description>Modifiez les informations du compte.</x-slot:description>

            @include('account.partials.form', [
                'action' => route('account.update', $account),
                'method' => 'put',
            ])

            <x-slot:footer>
                <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-primary-button form="account-form">Modifier</x-primary-button>
            </x-slot:footer>
        </x-card>

        <x-card danger>
            <x-slot:title>Archivage du compte</x-slot:title>
            <x-slot:description>
                Toutes les opérations du compte seront également archivées.
                Le compte et ses opérations pourront être désarchivés.
            </x-slot:description>

            <x-slot:footer>
                <form
                    method="post"
                    action="{{ route('account.destroy', $account) }}"
                    onsubmit="return confirm('Êtes-vous sûr et certain de vouloir archiver ce compte ?')"
                    class="flex items-center gap-6"
                >
                    @method('delete')
                    @csrf

                    <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                    <x-danger-button>Archiver</x-danger-button>
                </form>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
