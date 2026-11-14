<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            Création d'un portfolio
        </h2>
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
                <x-primary-button form="portfolio-form" icon="plus">Créer</x-primary-button>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
