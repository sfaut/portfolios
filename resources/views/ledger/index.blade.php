{{--
    Grand livre d'un portfolio : toutes les opérations de ses comptes, archivés compris, filtrées,
    triées par colonne, paginées ou toutes à la fois (bouton poussoir « Tout »), avec leurs totaux et
    leur export en CSV.

    Le composant Alpine ledger (resources/js/ledger.js) recharge le fragment « ledger » (totaux,
    outils et tableau) à chaque changement de filtre, de tri ou de page. Sans JavaScript, le
    formulaire des filtres, les en-têtes de colonne et la pagination rechargent la page.

    @var \App\Models\Portfolio $portfolio Portfolio du grand livre.
    @var \App\Support\Ledger\LedgerFilters $filters Filtres et tri en cours.
    @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\Operation>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Operation> $operations
        Opérations de la page, ou toutes sans pagination, avec leur compte et leurs lignes analytiques.
    @var bool $show_all Si toutes les opérations sont affichées, sans pagination.
    @var array{count: int, sales: string, payments: string, balance: string} $totals Totaux des opérations filtrées.
    @var int $operation_count Nombre d'opérations du portfolio, sans filtre.
    @var list<array{value: string, label: string, hint?: string}> $account_options Comptes proposés au filtre.
    @var list<array{value: string, label: string, hint?: string}> $axis_options Axes proposés au filtre.
    @var \App\Support\DateRestriction $written_restriction Jours d'écriture admis.
    @var \App\Support\DateRestriction $effective_restriction Dates effectives admises.
--}}
<x-app-layout wide>
    <x-slot name="header">
        <x-page-header>
            Grand livre
            <x-slot:description>Toutes les opérations du portfolio, tous comptes confondus, à filtrer, trier et exporter.</x-slot:description>
        </x-page-header>
    </x-slot>

    <div x-data="ledger(@js($filters->state()))" class="flex flex-col gap-6">

        <x-filter-bar
            id="ledger-filters"
            method="get"
            action="{{ route('ledger.index', $portfolio) }}"
            aria-label="Filtres du grand livre"
            x-on:submit="onSubmit($event)"
        >
            <x-filter-field label="Libellé" for="ledger-label" class="w-full sm:w-64">
                <x-text-input
                    id="ledger-label"
                    type="search"
                    name="label"
                    :value="$filters->label"
                    maxlength="{{ \App\Support\Ledger\LedgerFilters::LABEL_LENGTH }}"
                    placeholder="Rechercher un libellé"
                    autocomplete="off"
                    x-model.debounce.300ms="label"
                    class="w-full text-sm"
                />
            </x-filter-field>

            <x-filter-field label="Comptes">
                <x-multi-select
                    name="accounts"
                    label="Comptes"
                    :options="$account_options"
                    :value="$filters->accounts"
                    placeholder="Tous les comptes"
                    noun="comptes"
                    x-model="accounts"
                />
            </x-filter-field>

            <x-filter-field label="Axes analytiques">
                <x-multi-select
                    name="axes"
                    label="Axes analytiques"
                    :options="$axis_options"
                    :value="$filters->axes"
                    placeholder="Tous les axes"
                    noun="axes"
                    x-model="axes"
                />
            </x-filter-field>

            <x-filter-field label="Date effective">
                <x-date-picker
                    name="effective"
                    label="Date effective"
                    mode="range"
                    :value="$filters->effective"
                    :restriction="$effective_restriction"
                    :presets="\App\Enums\DatePreset::cases()"
                    placeholder="Toutes les dates"
                    x-model="effective"
                />
            </x-filter-field>

            <x-filter-field label="Date d'écriture">
                <x-date-picker
                    name="written"
                    label="Date d'écriture"
                    mode="range"
                    :value="$filters->written"
                    :restriction="$written_restriction"
                    :presets="\App\Enums\DatePreset::cases()"
                    placeholder="Toutes les dates"
                    x-model="written"
                />
            </x-filter-field>

            <x-filter-field label="Sens">
                <x-multi-select
                    name="flow"
                    label="Sens des montants"
                    :multiple="false"
                    :options="[['value' => 'sales', 'label' => 'Ventes'], ['value' => 'payments', 'label' => 'Paiements']]"
                    :value="$filters->flow"
                    placeholder="Tout"
                    x-model="flow"
                />
            </x-filter-field>

            <x-filter-field label="Pointage">
                <x-multi-select
                    name="ticked"
                    label="Pointage des opérations"
                    :multiple="false"
                    :options="[['value' => 'yes', 'label' => 'Pointées'], ['value' => 'no', 'label' => 'Non pointées']]"
                    :value="$filters->ticked"
                    placeholder="Toutes"
                    x-model="ticked"
                />
            </x-filter-field>

            <x-filter-field label="Montant (€)">
                <div class="flex items-center gap-2">
                    <x-number-input
                        name="amount[min]"
                        :value="$filters->amountMin"
                        min="0"
                        placeholder="Min"
                        aria-label="Montant minimal, sans le signe"
                        x-model.debounce.300ms="amountMin"
                        class="w-28 text-sm"
                    />
                    <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">–</span>
                    <x-number-input
                        name="amount[max]"
                        :value="$filters->amountMax"
                        min="0"
                        placeholder="Max"
                        aria-label="Montant maximal, sans le signe"
                        x-model.debounce.300ms="amountMax"
                        class="w-28 text-sm"
                    />
                </div>
            </x-filter-field>

            <x-slot:status>
                <x-lucide-loader-circle
                    x-show="loading"
                    style="display: none"
                    class="size-4 text-indigo-700 dark:text-indigo-400 motion-safe:animate-spin"
                    aria-hidden="true"
                />
                <span x-show="failed" style="display: none" role="alert" class="text-red-700 dark:text-red-400">
                    Le grand livre n'a pas pu être rechargé. Réessayez.
                </span>
                <noscript>
                    <x-button variant="primary" type="submit">Filtrer</x-button>
                </noscript>
                <a
                    href="{{ route('ledger.index', $portfolio) }}"
                    x-show="filtered"
                    @style(['display: none' => ! $filters->isFiltered()])
                    class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline"
                >Effacer les filtres</a>
            </x-slot:status>
        </x-filter-bar>

        <div
            x-ref="results"
            x-bind:aria-busy="loading"
            x-bind:class="loading && 'opacity-60'"
            x-on:click="onClick($event)"
            class="flex flex-col gap-6 transition-opacity motion-reduce:transition-none"
        >
            @fragment('ledger')
                {{-- Tri en cours, que le formulaire des filtres renvoie avec eux. --}}
                @unless ($filters->isDefaultSort())
                    <input type="hidden" form="ledger-filters" name="sort" value="{{ $filters->sort }}">
                    <input type="hidden" form="ledger-filters" name="dir" value="{{ $filters->direction }}">
                @endunless

                <x-summary>
                    <x-summary.item label="Opérations" :of="$filters->isFiltered() ? $operation_count : null">{{ Number::format($totals['count'], locale: 'fr') }}</x-summary.item>
                    <x-summary.item label="Ventes">{{ Number::amount($totals['sales'], currency: 'EUR') }}</x-summary.item>
                    <x-summary.item label="Paiements">{{ Number::amount($totals['payments'], currency: 'EUR') }}</x-summary.item>
                    <x-summary.item label="Solde" :class="\Illuminate\Support\Arr::toCssClasses([
                        'text-red-700 dark:text-red-400' => $totals['balance'] < 0,
                        'text-emerald-700 dark:text-emerald-400' => $totals['balance'] == 0,
                        'text-blue-700 dark:text-blue-400' => $totals['balance'] > 0,
                    ])">{{ Number::amount($totals['balance'], signed: true, currency: 'EUR') }}</x-summary.item>

                    <x-slot:tools>
                        @if ($totals['count'] > 0)
                            <x-button :href="route('ledger.export', ['portfolio' => $portfolio, ...$filters->query()])" icon="download">
                                Exporter en CSV
                            </x-button>
                        @endif
                        <x-toggle-button name="all" form="ledger-filters" :pressed="$show_all" aria-label="Toutes les opérations, sans pagination">Tout</x-toggle-button>
                        @unless ($show_all)
                            {{ $operations->links('pagination.narrow') }}
                        @endunless
                    </x-slot:tools>
                </x-summary>

                <div class="overflow-x-auto rounded-md shadow-sm">
                    <table class="w-full **:border-gray-200 dark:**:border-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold uppercase tracking-wide">
                            <tr>
                                <x-sortable-heading column="id" :sort="$filters->sort" :direction="$filters->direction" align="right">ID</x-sortable-heading>
                                <x-sortable-heading column="account" :sort="$filters->sort" :direction="$filters->direction" align="left" class="min-w-44">Compte</x-sortable-heading>
                                <x-sortable-heading column="written" :sort="$filters->sort" :direction="$filters->direction">Horodatage écriture</x-sortable-heading>
                                <x-sortable-heading column="effective" :sort="$filters->sort" :direction="$filters->direction">Date effective</x-sortable-heading>
                                <x-sortable-heading column="label" :sort="$filters->sort" :direction="$filters->direction" align="left" class="min-w-44">Libellé</x-sortable-heading>
                                <x-sortable-heading column="amount" :sort="$filters->sort" :direction="$filters->direction" align="right">Montant (€)</x-sortable-heading>
                                <x-sortable-heading column="axes" :sort="$filters->sort" :direction="$filters->direction" align="left">Axes</x-sortable-heading>
                                <x-sortable-heading column="ticked" :sort="$filters->sort" :direction="$filters->direction" align="center">Pointée ?</x-sortable-heading>
                                <th scope="col" class="border px-3 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 text-sm">
                            @forelse ($operations as $operation)
                                <tr class="hover:bg-yellow-100 dark:hover:bg-yellow-900/30">
                                    <td class="border px-3 py-2 text-right tabular-nums">{{ $operation->id }}</td>
                                    <td class="border px-3 py-2">
                                        <a
                                            href="{{ route('account.show', $operation->account) }}"
                                            class="text-indigo-700 dark:text-indigo-400 no-underline hover:underline"
                                        >{{ $operation->account->name }}</a>
                                        @if ($operation->account->trashed())
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(archivé)</span>
                                        @endif
                                    </td>
                                    <td class="border px-3 py-2 text-center whitespace-nowrap tabular-nums">{{ $operation->created_at?->format('d/m/Y H:i:s') }}</td>
                                    <td class="border px-3 py-2 text-center whitespace-nowrap tabular-nums">{{ date('d/m/Y', strtotime($operation->delivery_at)) }}</td>
                                    <td class="border px-3 py-2">{{ $operation->label }}</td>
                                    <td class="border px-3 py-2 text-right whitespace-nowrap tabular-nums">{{ Number::amount($operation->amount) }}</td>
                                    <td class="border px-3 py-2"><x-analytic-breakdown :operation="$operation"/></td>
                                    <td class="relative border px-3 py-2 text-center">
                                        <x-tick-box :operation="$operation" :disabled="$operation->account->trashed()"/>
                                    </td>
                                    <td class="border px-3 py-2 text-center">
                                        @unless ($operation->account->trashed())
                                            <a
                                                href="{{ route('operation.edit', $operation) }}"
                                                class="inline-flex items-center gap-1 text-xs font-semibold uppercase text-indigo-700 dark:text-indigo-400 no-underline hover:underline"
                                            ><x-lucide-pencil class="size-3.5 shrink-0" aria-hidden="true"/>Modifier</a>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-6 text-center">
                                        @if ($filters->isFiltered())
                                            Aucune opération ne correspond aux filtres.<br>
                                            <a href="{{ route('ledger.index', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Effacer les filtres</a>
                                        @else
                                            Pas encore d'opération dans ce portfolio : elles s'écrivent depuis un compte.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endfragment
        </div>

    </div>

</x-app-layout>
