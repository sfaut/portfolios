<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold">Création de compte</h2>
            <x-breadcrumb :links="[route('portfolio.show', $portfolio) => 'Comptes']"/>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations du compte</x-slot:title>
            <x-slot:description>Renseignez les informations de votre nouveau compte.</x-slot:description>

            @include('account.partials.form', [
                'action' => route('account.store', $portfolio),
                'method' => 'post',
            ])

            <x-slot:footer>
                <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-primary-button form="account-form" icon="plus">Créer</x-primary-button>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
