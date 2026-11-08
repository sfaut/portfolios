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

        @if ($account->trashed())
            <x-card>
                <x-slot:title>Désarchivage du compte</x-slot:title>
                <x-slot:description>
                    Le compte est archivé : ses opérations sont en lecture seule.
                    Une fois désarchivé, il retrouve sa place parmi les comptes du portfolio.
                </x-slot:description>

                <x-slot:footer>
                    <form method="post" action="{{ route('account.restore', $account) }}" class="flex items-center gap-6">
                        @method('patch')
                        @csrf

                        <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                        <x-primary-button>Désarchiver</x-primary-button>
                    </form>
                </x-slot:footer>
            </x-card>
        @else
            <x-card danger>
                <x-slot:title>Archivage du compte</x-slot:title>
                <x-slot:description>
                    Toutes les opérations du compte seront également archivées.
                    Le compte et ses opérations pourront être désarchivés.
                </x-slot:description>

                <x-slot:footer>
                    <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                    <x-danger-button type="button" commandfor="archive-account" command="show-modal">Archiver</x-danger-button>
                </x-slot:footer>
            </x-card>

            <x-dialog id="archive-account" danger>
                <x-slot:title>Archiver le compte « {{ $account->name }} » ?</x-slot:title>
                <x-slot:description>
                    Il pourra être désarchivé à tout moment depuis « Modifier le compte ».
                </x-slot:description>

                <x-slot:footer>
                    <button type="button" commandfor="archive-account" command="close" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</button>

                    <form method="post" action="{{ route('account.destroy', $account) }}">
                        @method('delete')
                        @csrf

                        <x-danger-button>Archiver le compte</x-danger-button>
                    </form>
                </x-slot:footer>
            </x-dialog>
        @endif

    </div>

</x-app-layout>
