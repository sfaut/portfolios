<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            Modification du portfolio
            <span class="text-indigo-700 dark:text-indigo-400">{{ $portfolio->name }}</span>
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6 pb-6">

        <div class="shadow-sm rounded-md p-6 bg-white dark:bg-gray-800">
            <header>
                <h2 class="text-lg font-semibold">Informations du portfolio</h2>
                <p class="mt-1 text-sm text-gray-400">Modifiez les informations du portfolio.</p>
            </header>

            @include('portfolio.partials.form', [
                'action' => route('portfolio.update', $portfolio),
                'method' => 'put',
                'button' => 'Modifier',
                'abort' => route('portfolio.show', $portfolio),
            ])
        </div>

        <div @class(['shadow-sm', 'rounded-md', 'p-6', 'bg-white', 'dark:bg-gray-800', 'ring-2', 'ring-red-800', 'dark:ring-red-600'])>
            <header>
                <h2 class="text-lg font-semibold">Archivage du portfolio</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Le portfolio sera archivé.
                    Le portfolio, ses comptes et ses opérations ne pourront plus être consultées.
                    Le portfolio pourra toujours être désarchvé et de nouveau consulté.
                </p>
            </header>

            <form method="post" action="{{ route('portfolio.destroy', $portfolio) }}">
                @method('delete')
                @csrf

                <div class="flex items-center gap-6">
                    <x-danger-button>
                        Archiver
                    </x-danger-button>
                    <a href="{{ route('portfolio.show', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                </div>
            </form>

        </div>
    </div>

</x-app-layout>
