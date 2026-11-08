{{--
    Portfolio et ses comptes : comptes actifs, puis comptes archivés dans une section repliée.

    Le champ de recherche filtre les deux listes sur le nom des comptes (composant Alpine accountSearch).
    Le fragment accounts, que le contrôleur renvoie seul aux requêtes ajax, porte les résultats et leur
    résumé (data-summary), que le composant reporte à droite du champ.

    @var \App\Models\Portfolio $portfolio Portfolio affiché.
    @var string $search Recherche sur le nom des comptes, vide sans recherche.
    @var string $summary Nombre de comptes trouvés sur le total du portfolio, vide sans recherche.
    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountReport> $accounts Comptes actifs, filtrés par la recherche.
    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountReport> $archived_accounts Comptes archivés, filtrés par la recherche.
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

    <div x-data="accountSearch(@js($search))" class="max-w-6xl mx-auto flex flex-col gap-6">

        @if (session('message'))
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        @endif

        <div class="flex justify-between items-center gap-4">
            <form
                method="get"
                action="{{ route('portfolio.show', $portfolio) }}"
                role="search"
                x-on:submit.prevent="refresh()"
                class="flex flex-1 min-w-0 items-center gap-4"
            >
                <label for="account-search" class="sr-only">Rechercher un compte</label>
                <div class="relative w-full max-w-sm">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500">
                        <x-lucide-search x-show="! loading" class="size-4" aria-hidden="true"/>
                        <x-lucide-loader-circle
                            x-show="loading"
                            style="display: none"
                            class="size-4 text-indigo-700 dark:text-indigo-400 motion-safe:animate-spin"
                            aria-hidden="true"
                        />
                    </span>
                    <x-text-input
                        id="account-search"
                        type="search"
                        name="search"
                        :value="$search"
                        maxlength="50"
                        placeholder="Rechercher un compte"
                        autocomplete="off"
                        x-ref="input"
                        x-model="search"
                        x-on:input="loading = true"
                        x-on:input.debounce.300ms="refresh()"
                        x-on:keydown.escape.prevent="clear()"
                        class="w-full px-9 [&::-webkit-search-cancel-button]:hidden"
                    />
                    <button
                        type="button"
                        x-show="search !== ''"
                        x-on:click="clear()"
                        @style(['display: none' => $search === ''])
                        aria-label="Effacer la recherche"
                        class="absolute inset-y-0 right-0 flex items-center rounded-md px-3 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 focus-visible:outline-2 focus-visible:outline-indigo-500"
                    >
                        <x-lucide-x class="size-4" aria-hidden="true"/>
                    </button>
                </div>
                <span
                    x-ref="summary"
                    x-show="! failed"
                    role="status"
                    class="truncate text-sm text-gray-500 dark:text-gray-400"
                >{{ $summary }}</span>
                <span
                    x-show="failed"
                    style="display: none"
                    role="alert"
                    class="truncate text-sm text-red-700 dark:text-red-400"
                >La recherche n'a pas abouti. Réessayez.</span>
            </form>

            <div class="shrink-0">
                <x-link.primary :target="route('account.create', $portfolio)">Créer un compte</x-link.primary>
            </div>
        </div>

        <div
            x-ref="results"
            x-bind:aria-busy="loading"
            x-bind:class="loading && 'opacity-60'"
            class="flex flex-col gap-6 transition-opacity duration-150"
        >
            @fragment('accounts')
                <span data-summary hidden>{{ $summary }}</span>

                @if ($search !== '' && $accounts->isEmpty() && $archived_accounts->isEmpty())
                    <div class="flex flex-col items-center gap-4 rounded-md border-2 border-dashed border-gray-300 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                        <p>Aucun compte ne correspond à « {{ $search }} ».</p>
                        <x-secondary-button x-on:click="clear()">Effacer la recherche</x-secondary-button>
                    </div>
                @endif

                @if ($search === '' || $accounts->isNotEmpty())
                    <div class="grid gap-6 grid-cols-3">

                        @foreach ($accounts as $account)
                            @include('account.partials.card', ['account' => $account])
                        @endforeach

                        @if ($search === '')
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
                                <x-lucide-circle-plus class="mx-auto size-7.5 stroke-[1.25]" aria-hidden="true"/>
                                <h3>Créer un compte</h3>
                            </a>
                        @endif
                    </div>
                @endif

                @if ($archived_accounts->isNotEmpty())
                    <section x-data="{ open: @js($search !== '') }" class="flex flex-col gap-4">
                        <h3>
                            <button
                                type="button"
                                x-on:click="open = ! open"
                                x-bind:aria-expanded="open"
                                aria-controls="archived-accounts"
                                class="flex items-center gap-2 text-lg font-semibold text-gray-700 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-400"
                            >
                                <x-lucide-chevron-right x-bind:class="open && 'rotate-90'" class="size-4 transition-transform duration-200" aria-hidden="true"/>
                                Comptes archivés
                                <span class="rounded-full bg-gray-200 dark:bg-gray-700 px-2 text-sm text-gray-600 dark:text-gray-300">{{ $archived_accounts->count() }}</span>
                            </button>
                        </h3>

                        <div id="archived-accounts" x-show="open" @style(['display: none' => $search === '']) class="grid gap-6 grid-cols-3">
                            @foreach ($archived_accounts as $account)
                                @include('account.partials.card', ['account' => $account])
                            @endforeach
                        </div>
                    </section>
                @endif
            @endfragment
        </div>

    </div>

</x-app-layout>
