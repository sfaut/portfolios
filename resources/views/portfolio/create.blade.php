<x-app-layout>

    <x-slot name="header">
        <x-page-header>
            Création d'un portfolio
            <x-slot:description>Un portfolio regroupe des comptes, leurs opérations et leurs axes analytiques.</x-slot:description>
        </x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations du portfolio</x-slot:title>
            <x-slot:description>Renseignez les informations de votre nouveau portfolio.</x-slot:description>

            @include('portfolio.partials.form', [
                'action' => route('portfolio.store'),
                'method' => 'post',
            ])

            <x-slot:footer>
                <a href="{{ route('portfolio.index') }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-button variant="primary" type="submit" form="portfolio-form" icon="plus">Créer</x-button>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
