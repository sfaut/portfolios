<x-app-layout>
    <x-slot name="header">
        <x-page-header :breadcrumb="[route('portfolio.show', $portfolio) => 'Comptes']">
            Création de compte
            <x-slot:description>Un compte regroupe des opérations datées, et peut proposer une répartition analytique par défaut.</x-slot:description>
        </x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations du compte</x-slot:title>
            <x-slot:description>Renseignez les informations de votre nouveau compte.</x-slot:description>

            @include('account.partials.form', [
                'action' => route('account.store', $portfolio),
                'method' => 'post',
            ])

            <x-slot:footer>
                <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-button variant="primary" type="submit" form="account-form" icon="plus">Créer</x-button>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
