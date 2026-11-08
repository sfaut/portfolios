<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Création de compte</h2>

        <div class="text-sm text-gray-400">
            ➜ Portfolio
            <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $portfolio->name }}</a>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations du compte</x-slot:title>
            <x-slot:description>Renseignez les informations de votre nouveau compte.</x-slot:description>

            @include('account.partials.form', [
                'action' => route('account.store', $portfolio),
                'method' => 'post',
                'button' => 'Créer',
                'abort' => route('portfolio.show', $portfolio),
            ])
        </x-card>

    </div>

</x-app-layout>
