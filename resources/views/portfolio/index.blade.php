<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold">
                Portfolios
            </h2>
            <div class="text-sm text-gray-400">
                {{ count($portfolios) }}
                {{ Str::of('portfolio')->plural(count($portfolios)) }}
                {{ Str::of('enregistré')->plural(count($portfolios)) }}
            </div>
        </div>
    </x-slot>

    @if (session('message'))
        <div class="max-w-2xl mx-auto mb-6">
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        </div>
    @endif

    <div class="max-w-2xl mx-auto flex flex-col gap-6 pb-6">

        @foreach($portfolios as $portfolio)

            <a
                href="{{ route('portfolio.show', $portfolio) }}"

                @class([
                    'flex', 'rounded-md',
                    'bg-white', 'dark:bg-gray-800', 'p-4', 'shadow-sm',
                    'hover:bg-indigo-700', 'hover:text-white',
                    'transition-all', 'duration-200', 'hover:scale-102',
                    'items-center',
                ])
            >
                <div>
                    <h3 class="text-xl font-semibold">{{ $portfolio->name }}</h3>
                    <p class="text-sm text-gray-400">{{ trim($portfolio->description) ?? '--' }}</p>
                </div>

                <svg class="self-center ml-auto" preserveAspectRatio="none" width="40" height="40" viewBox="0 0 10 10">
                    <polyline
                        points="3,1 7,5 3,9"
                        stroke="currentColor" stroke-width="0.4" fill="none"
                    />
                </svg>
            </a>

        @endforeach

        <a
            href="{{ route('portfolio.create') }}"
            @class([
                'block',
                'rounded-md', 'bg-white', 'dark:bg-gray-800', 'shadow-sm', 'hover:shadow-md',
                'p-4',
                'border-2', 'border-dashed', 'border-gray-400', 'dark:border-gray-600',
                'hover:border-indigo-700', 'hover:text-indigo-700', 'dark:hover:border-indigo-400', 'dark:hover:text-indigo-400',
                'text-center', 'text-gray-400', 'dark:text-gray-500',
                'transition-all', 'duration-200', 'ease-linear', 'hover:scale-102',
            ])
        >
            <svg width="30" height="30" viewBox="0 0 10 10" fill="none" class="mx-auto">
                <line x1="5" y1="2.5" x2="5" y2="7.5" stroke="currentColor" stroke-width="0.5"/>
                <line x1="2.5" y1="5" x2="7.5" y2="5" stroke="currentColor" stroke-width="0.5"/>
                <circle cx="5" cy="5" r="4" stroke="currentColor" stroke-width="0.5"/>
            </svg>
            <h3>Créer un portfolio</h3>
        </a>
    </div>

</x-app-layout>
