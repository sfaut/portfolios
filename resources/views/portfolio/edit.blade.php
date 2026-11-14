<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold">Modification du portfolio</h2>
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
                <x-primary-button form="portfolio-form" icon="check">Modifier</x-primary-button>
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
                <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-danger-button type="button" commandfor="archive-portfolio" command="show-modal">Archiver</x-danger-button>
            </x-slot:footer>
        </x-card>

        <x-dialog id="archive-portfolio" danger>
            <x-slot:title>Archiver le portfolio « {{ $portfolio->name }} » ?</x-slot:title>
            <x-slot:description>
                Le portfolio, ses comptes et ses opérations ne seront plus consultables.
            </x-slot:description>

            <x-slot:footer>
                <button type="button" commandfor="archive-portfolio" command="close" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</button>

                <form method="post" action="{{ route('portfolio.destroy', $portfolio) }}">
                    @method('delete')
                    @csrf

                    <x-danger-button>Archiver le portfolio</x-danger-button>
                </form>
            </x-slot:footer>
        </x-dialog>

    </div>

</x-app-layout>
