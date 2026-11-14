{{--
    Création d'un axe analytique.

    @var \App\Models\Portfolio $portfolio Portfolio qui recevra l'axe.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-page-header :breadcrumb="[route('analytic-axis.index', $portfolio) => 'Axes analytiques']">
            Création d'un axe analytique
            <x-slot:description>Un axe se reconnaît à son nom, son icône et sa couleur, dans chaque répartition.</x-slot:description>
        </x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations de l'axe</x-slot:title>
            <x-slot:description>Renseignez les informations du nouvel axe analytique.</x-slot:description>

            @include('analytic-axis.partials.form', [
                'action' => route('analytic-axis.store', $portfolio),
                'method' => 'post',
            ])

            <x-slot:footer>
                <a href="{{ route('analytic-axis.index', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-button variant="primary" type="submit" form="analytic-axis-form" icon="plus">Créer</x-button>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
