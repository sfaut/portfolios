<x-app-layout>
    <x-slot name="header">
        <x-page-header :breadcrumb="[route('portfolio.show', $account->portfolio) => 'Comptes', route('account.show', $account) => $account->name]">
            Modification du compte <span class="text-indigo-700 dark:text-indigo-400">{{ $account->name }}</span>
            <x-slot:description>Un compte regroupe des opérations datées, et peut proposer une répartition analytique par défaut.</x-slot:description>
        </x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations du compte</x-slot:title>
            <x-slot:description>Modifiez les informations du compte.</x-slot:description>

            @include('account.partials.form', [
                'action' => route('account.update', $account),
                'method' => 'put',
            ])

            <x-slot:footer>
                <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-button variant="primary" type="submit" form="account-form" icon="check">Modifier</x-button>
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
                        <x-button variant="primary" type="submit">Désarchiver</x-button>
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
                    <x-button variant="danger" type="button" commandfor="archive-account" command="show-modal">Archiver</x-button>
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

                        <x-button variant="danger" type="submit">Archiver le compte</x-button>
                    </form>
                </x-slot:footer>
            </x-dialog>
        @endif

    </div>

</x-app-layout>
