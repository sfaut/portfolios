<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            Modification du portfolio
            <span class="text-indigo-700 dark:text-indigo-400">{{ $portfolio->name }}</span>
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations du portfolio</x-slot:title>
            <x-slot:description>Modifiez les informations du portfolio.</x-slot:description>

            @include('portfolio.partials.form', [
                'action' => route('portfolio.update', $portfolio),
                'method' => 'put',
            ])

            <x-slot:footer>
                <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-primary-button form="portfolio-form">Modifier</x-primary-button>
            </x-slot:footer>
        </x-card>

        <x-card danger>
            <x-slot:title>Archivage du portfolio</x-slot:title>
            <x-slot:description>
                Le portfolio sera archivé.
                Le portfolio, ses comptes et ses opérations ne pourront plus être consultées.
                Le portfolio pourra toujours être désarchvé et de nouveau consulté.
            </x-slot:description>

            <x-slot:footer>
                <form method="post" action="{{ route('portfolio.destroy', $portfolio) }}" class="flex items-center gap-6">
                    @method('delete')
                    @csrf

                    <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                    <x-danger-button>Archiver</x-danger-button>
                </form>
            </x-slot:footer>
        </x-card>

    </div>

</x-app-layout>
