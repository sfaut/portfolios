<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            Création d'un portfolio
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto shadow-sm rounded-md bg-white space-y-6 p-6">

        <header>
            <h2 class="text-lg font-semibold">Informations du portfolio</h2>
            <p class="mt-1 text-sm text-gray-400">Renseignez les informations de votre nouveau portfolio.</p>
        </header>

        @include('portfolio.partials.form', [
            'action' => route('portfolio.store'),
            'method' => 'post',
            'button' => 'Créer',
            'abort' => route('portfolio.index'),
        ])

    </div>

</x-app-layout>
