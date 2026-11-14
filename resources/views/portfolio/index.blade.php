{{--
    Liste des portfolios de l'utilisateur, suivie de la carte de création.

    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Portfolio> $portfolios Portfolios de l'utilisateur.
--}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold">
                Portfolios
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ count($portfolios) }}
                {{ Str::of('portfolio')->plural(count($portfolios)) }}
                {{ Str::of('enregistré')->plural(count($portfolios)) }}
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

        @if (session('message'))
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        @endif

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($portfolios as $portfolio)

                <a
                    href="{{ route('portfolio.dashboard', $portfolio) }}"

                    @class([
                        'group', 'flex', 'items-center', 'justify-between', 'gap-4',
                        'rounded-md', 'bg-white', 'dark:bg-gray-800', 'p-4', 'shadow-sm',
                        'hover:bg-indigo-700', 'hover:text-white',
                        'transition-all', 'duration-200', 'hover:scale-102',
                    ])
                >
                    <div>
                        <h3 class="text-xl font-semibold">{{ $portfolio->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 group-hover:text-indigo-200">{{ filled($portfolio->description) ? $portfolio->description : '--' }}</p>
                    </div>

                    <x-lucide-chevron-right class="size-10 shrink-0 stroke-1" aria-hidden="true"/>
                </a>

            @endforeach

            <a
                href="{{ route('portfolio.create') }}"
                @class([
                    'flex', 'flex-col', 'justify-center',
                    'rounded-md', 'bg-white', 'dark:bg-gray-800', 'shadow-sm', 'hover:shadow-md',
                    'p-4',
                    'border-2', 'border-dashed', 'border-gray-400', 'dark:border-gray-600',
                    'hover:border-indigo-700', 'hover:text-indigo-700', 'dark:hover:border-indigo-400', 'dark:hover:text-indigo-400',
                    'text-center', 'text-gray-400', 'dark:text-gray-500',
                    'transition-all', 'duration-200', 'ease-linear', 'hover:scale-102',
                ])
            >
                <x-lucide-circle-plus class="mx-auto size-7.5 stroke-[1.25]" aria-hidden="true"/>
                <h3>Créer un portfolio</h3>
            </a>
        </div>
    </div>

</x-app-layout>
