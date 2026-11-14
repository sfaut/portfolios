{{--
    Portfolio et ses comptes : comptes actifs, puis comptes archivés dans une section repliée.

    Deux filtres cumulables réduisent les deux listes (composant Alpine accountSearch) : la recherche
    sur le nom des comptes, et le mois, qui garde les comptes ayant une opération dans ce mois. Le
    fragment accounts, que le contrôleur renvoie seul aux requêtes ajax, porte les résultats et leur
    résumé (data-summary), que le composant reporte à droite des filtres.

    @var \App\Models\Portfolio $portfolio Portfolio affiché.
    @var string $search Recherche sur le nom des comptes, vide sans recherche.
    @var string $month Mois d'opération retenu (« 2026-11 »), vide sans filtre de mois.
    @var \App\Support\MonthRestriction $month_restriction Mois choisissables : ceux où le portfolio a des opérations.
    @var bool $filtered Si un filtre au moins est actif.
    @var string $summary Nombre de comptes trouvés sur le total du portfolio, vide sans filtre.
    @var string $empty_message Message affiché quand les filtres ne trouvent aucun compte.
    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountReport> $accounts Comptes actifs, filtrés.
    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountReport> $archived_accounts Comptes archivés, filtrés.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Comptes</h2>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ count($portfolio->accounts) }}
            {{ Str::of('comptes')->plural(count($portfolio->accounts)) }}
            {{ Str::of('enregistré')->plural(count($portfolio->accounts)) }}
        </div>
    </x-slot>

    <div x-data="accountSearch(@js($search), @js($month))" class="flex flex-col gap-6">

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
                        class="w-full px-9 text-sm [&::-webkit-search-cancel-button]:hidden"
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

                @if ($month_restriction->allow !== [])
                    <x-month-picker
                        name="month"
                        :restriction="$month_restriction"
                        :value="$month"
                        label="Mois d'opération"
                        placeholder="Tous les mois"
                        x-model="month"
                        class="text-sm"
                    />
                @endif
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
                <x-link.primary :target="route('account.create', $portfolio)" icon="plus">Créer un compte</x-link.primary>
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

                @if ($filtered && $accounts->isEmpty() && $archived_accounts->isEmpty())
                    <div class="flex flex-col items-center gap-4 rounded-md border-2 border-dashed border-gray-300 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                        <p>{{ $empty_message }}</p>
                        <x-secondary-button x-on:click="clearFilters()">Effacer les filtres</x-secondary-button>
                    </div>
                @endif

                @if (! $filtered || $accounts->isNotEmpty())
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

                        @foreach ($accounts as $account)
                            @include('account.partials.card', ['account' => $account])
                        @endforeach

                        @unless ($filtered)
                            <x-create-card :href="route('account.create', $portfolio)">Créer un compte</x-create-card>
                        @endunless
                    </div>
                @endif

                @if ($archived_accounts->isNotEmpty())
                    <section x-data="{ open: @js($filtered) }" class="flex flex-col gap-4">
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

                        <div id="archived-accounts" x-show="open" @style(['display: none' => ! $filtered]) class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
