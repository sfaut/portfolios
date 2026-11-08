<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold">Axes analytiques</h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                ➜ Portfolio
                <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $portfolio->name }}</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto flex flex-col gap-6">

        @if (session('message'))
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        @endif

        <div class="flex items-center justify-between gap-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Les axes répartissent le montant d'une opération, en pourcentages, depuis son formulaire.
            </p>
            <div class="shrink-0">
                <x-link.primary :target="route('analytic-axis.create', $portfolio)">Créer un axe</x-link.primary>
            </div>
        </div>

        @if ($analytic_axes->isNotEmpty())
            @include('analytic-axis.partials.table', ['analytic_axes' => $analytic_axes])
        @else
            <div class="rounded-md bg-white dark:bg-gray-800 p-4 text-center text-sm shadow-sm">
                Pas encore d'axe analytique dans ce portfolio.
            </div>
        @endif

        @if ($archived_analytic_axes->isNotEmpty())
            <section x-data="{ open: false }" class="flex flex-col gap-4">
                <h3>
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        x-bind:aria-expanded="open"
                        aria-controls="archived-analytic-axes"
                        class="flex items-center gap-2 text-lg font-semibold text-gray-700 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-400"
                    >
                        <svg x-bind:class="open && 'rotate-90'" class="size-4 transition-transform duration-200" viewBox="0 0 10 10" aria-hidden="true">
                            <polyline points="3,1 7,5 3,9" stroke="currentColor" stroke-width="1.2" fill="none"/>
                        </svg>
                        Axes archivés
                        <span class="rounded-full bg-gray-200 dark:bg-gray-700 px-2 text-sm text-gray-600 dark:text-gray-300">{{ $archived_analytic_axes->count() }}</span>
                    </button>
                </h3>

                <div id="archived-analytic-axes" x-show="open" style="display: none" class="opacity-75">
                    @include('analytic-axis.partials.table', ['analytic_axes' => $archived_analytic_axes])
                </div>
            </section>
        @endif

    </div>

</x-app-layout>
