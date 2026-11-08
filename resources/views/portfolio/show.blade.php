{{--
    Portfolio et ses comptes : comptes actifs, puis comptes archivés dans une section repliée.

    @var \App\Models\Portfolio $portfolio Portfolio affiché.
    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountReport> $accounts Comptes actifs.
    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountReport> $archived_accounts Comptes archivés.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold">
                    Portfolio <span class="text-indigo-700 dark:text-indigo-400">{{ $portfolio->name }}</span>
                </h2>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($portfolio->accounts) }}
                    {{ Str::of('comptes')->plural(count($portfolio->accounts)) }}
                    {{ Str::of('enregistré')->plural(count($portfolio->accounts)) }}
                </div>
            </div>
            <div class="flex items-center gap-4">
                <x-link.secondary :target="route('analytic-axis.index', $portfolio)">Axes analytiques</x-link.secondary>
                <x-link.secondary :target="route('portfolio.edit', $portfolio)">Modifier le portfolio</x-link.secondary>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto flex flex-col gap-6">

        @if (session('message'))
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        @endif

        <div class="flex justify-end">
            <x-link.primary :target="route('account.create', $portfolio)">Créer un compte</x-link.primary>
        </div>

        <div class="grid gap-6 grid-cols-3">

            @foreach ($accounts as $account)
                @include('account.partials.card', ['account' => $account])
            @endforeach

            <a
                href="{{ route('account.create', $portfolio) }}"
                @class([
                    'col-start-1', 'block',
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
                <h3>Créer un compte</h3>
            </a>
        </div>

        @if ($archived_accounts->isNotEmpty())
            <section x-data="{ open: false }" class="flex flex-col gap-4">
                <h3>
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        x-bind:aria-expanded="open"
                        aria-controls="archived-accounts"
                        class="flex items-center gap-2 text-lg font-semibold text-gray-700 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-400"
                    >
                        <svg x-bind:class="open && 'rotate-90'" class="size-4 transition-transform duration-200" viewBox="0 0 10 10" aria-hidden="true">
                            <polyline points="3,1 7,5 3,9" stroke="currentColor" stroke-width="1.2" fill="none"/>
                        </svg>
                        Comptes archivés
                        <span class="rounded-full bg-gray-200 dark:bg-gray-700 px-2 text-sm text-gray-600 dark:text-gray-300">{{ $archived_accounts->count() }}</span>
                    </button>
                </h3>

                <div id="archived-accounts" x-show="open" style="display: none" class="grid gap-6 grid-cols-3">
                    @foreach ($archived_accounts as $account)
                        @include('account.partials.card', ['account' => $account])
                    @endforeach
                </div>
            </section>
        @endif

    </div>

</x-app-layout>
