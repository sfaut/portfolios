{{--
    Liste des portfolios de l'utilisateur, en cartes (<x-link-card>) qui mènent chacune à son tableau
    de bord, suivie de la carte de création.

    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Portfolio> $portfolios
        Portfolios de l'utilisateur, avec accounts_count (comptes actifs).
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
            @foreach ($portfolios as $portfolio)
                <x-link-card :href="route('portfolio.dashboard', $portfolio)">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <h3 class="truncate font-semibold">{{ $portfolio->name }}</h3>
                        @if (filled($portfolio->description))
                            <p class="line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $portfolio->description }}</p>
                        @endif
                    </div>

                    <x-slot:stats>
                        <x-link-card.stat icon="wallet">
                            @if ($portfolio->accounts_count > 0)
                                {{ trans_choice(':count compte|:count comptes', $portfolio->accounts_count) }}
                            @else
                                Aucun compte
                            @endif
                        </x-link-card.stat>
                    </x-slot:stats>
                </x-link-card>
            @endforeach

            <x-create-card :href="route('portfolio.create')">Créer un portfolio</x-create-card>
        </div>
    </div>

</x-app-layout>
