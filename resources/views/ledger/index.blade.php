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
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Grand livre</h2>
    </x-slot>

    <div x-data="ledger(@js($filters->state()))" class="max-w-7xl mx-auto flex flex-col gap-6">

        <form
            id="ledger-filters"
            method="get"
            action="{{ route('ledger.index', $portfolio) }}"
            role="search"
            aria-label="Filtres du grand livre"
            x-on:submit="onSubmit($event)"
            class="flex flex-wrap items-end gap-x-4 gap-y-3 rounded-md bg-white dark:bg-gray-800 p-4 text-sm shadow-sm"
        >
            <div class="flex w-full flex-col gap-1 sm:w-64">
                <label for="ledger-label" class="text-xs font-medium text-gray-500 dark:text-gray-400">Libellé</label>
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
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">Comptes</span>
                <x-multi-select
                    name="accounts"
                    label="Comptes"
                    :options="$account_options"
                    :value="$filters->accounts"
                    placeholder="Tous les comptes"
                    noun="comptes"
                    x-model="accounts"
                />
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">Axes analytiques</span>
                <x-multi-select
                    name="axes"
                    label="Axes analytiques"
                    :options="$axis_options"
                    :value="$filters->axes"
                    placeholder="Tous les axes"
                    noun="axes"
                    x-model="axes"
                />
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">Date effective</span>
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
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">Date d'écriture</span>
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
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">Sens</span>
                <x-switch
                    label="Sens des montants"
                    name="flow"
                    :options="['' => 'Tout', 'sales' => 'Ventes', 'payments' => 'Paiements']"
                    :value="$filters->flow"
                    model="flow"
                    class="py-0.5"
                />
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">Pointage</span>
                <x-switch
                    label="Pointage des opérations"
                    name="ticked"
                    :options="['' => 'Toutes', 'yes' => 'Pointées', 'no' => 'Non pointées']"
                    :value="$filters->ticked"
                    model="ticked"
                    class="py-0.5"
                />
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">Montant (€)</span>
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
            </div>

            <div class="flex grow items-center justify-end gap-4 self-end py-2">
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
                    <x-primary-button>Filtrer</x-primary-button>
                </noscript>
                <a
                    href="{{ route('ledger.index', $portfolio) }}"
                    x-show="filtered"
                    @style(['display: none' => ! $filters->isFiltered()])
                    class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline"
                >Effacer les filtres</a>
            </div>
        </form>

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

                <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-4">
                    <dl role="status" class="flex flex-wrap gap-x-10 gap-y-2">
                        <div class="flex flex-col">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Opérations</dt>
                            <dd class="text-lg font-semibold tabular-nums">
                                {{ Number::format($totals['count'], locale: 'fr') }}
                                @if ($filters->isFiltered())
                                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">sur {{ Number::format($operation_count, locale: 'fr') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex flex-col">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Ventes</dt>
                            <dd class="text-lg font-semibold tabular-nums">{{ Number::amount($totals['sales'], currency: 'EUR') }}</dd>
                        </div>
                        <div class="flex flex-col">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Paiements</dt>
                            <dd class="text-lg font-semibold tabular-nums">{{ Number::amount($totals['payments'], currency: 'EUR') }}</dd>
                        </div>
                        <div class="flex flex-col">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Solde</dt>
                            <dd @class([
                                'text-lg font-semibold tabular-nums',
                                'text-red-700 dark:text-red-400' => $totals['balance'] < 0,
                                'text-emerald-700 dark:text-emerald-400' => $totals['balance'] == 0,
                                'text-blue-700 dark:text-blue-400' => $totals['balance'] > 0,
                            ])>{{ Number::amount($totals['balance'], signed: true, currency: 'EUR') }}</dd>
                        </div>
                    </dl>

                    <div class="flex items-center gap-3">
                        @if ($totals['count'] > 0)
                            <x-link.secondary :target="route('ledger.export', ['portfolio' => $portfolio, ...$filters->query()])" class="inline-flex items-center gap-2">
                                <x-lucide-download class="size-4" aria-hidden="true"/>
                                Exporter en CSV
                            </x-link.secondary>
                        @endif
                        <x-toggle-button name="all" form="ledger-filters" :pressed="$show_all" aria-label="Toutes les opérations, sans pagination">Tout</x-toggle-button>
                        @unless ($show_all)
                            {{ $operations->links('pagination.narrow') }}
                        @endunless
                    </div>
                </div>

                <div class="overflow-x-auto rounded-md shadow-sm">
                    <table class="w-full **:border-gray-200 dark:**:border-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold uppercase tracking-wide">
                            <tr>
                                <x-sortable-heading column="id" :sort="$filters->sort" :direction="$filters->direction" align="right">ID</x-sortable-heading>
                                <x-sortable-heading column="account" :sort="$filters->sort" :direction="$filters->direction" align="left">Compte</x-sortable-heading>
                                <x-sortable-heading column="written" :sort="$filters->sort" :direction="$filters->direction">Horodatage écriture</x-sortable-heading>
                                <x-sortable-heading column="effective" :sort="$filters->sort" :direction="$filters->direction">Date effective</x-sortable-heading>
                                <x-sortable-heading column="label" :sort="$filters->sort" :direction="$filters->direction" align="left">Libellé</x-sortable-heading>
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
                                    <td class="border px-3 py-2">
                                        @if ($operation->analyticLines->isNotEmpty())
                                            <x-tooltip>
                                                <span class="flex flex-wrap gap-1">
                                                    @foreach ($operation->analyticLines as $line)
                                                        <span class="rounded-full bg-indigo-100 dark:bg-indigo-900/50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $line->analyticAxis->name }}</span>
                                                    @endforeach
                                                </span>

                                                <x-slot:content>
                                                    <span class="grid grid-cols-[1fr_auto_auto] gap-x-4 gap-y-1">
                                                        @foreach ($operation->analyticLines as $line)
                                                            <span>{{ $line->analyticAxis->name }}</span>
                                                            <span class="text-right tabular-nums">{{ Number::percentage($line->percentage, maxPrecision: 2, locale: 'fr') }}</span>
                                                            <span class="text-right tabular-nums text-gray-400">{{ Number::amount($line->amountShare($operation->amount), currency: 'EUR') }}</span>
                                                        @endforeach
                                                    </span>
                                                </x-slot:content>
                                            </x-tooltip>
                                        @endif
                                    </td>
                                    <td class="relative border px-3 py-2 text-center">
                                        <x-tick-box :operation="$operation" :disabled="$operation->account->trashed()"/>
                                    </td>
                                    <td class="border px-3 py-2 text-center">
                                        @unless ($operation->account->trashed())
                                            <a
                                                href="{{ route('operation.edit', $operation) }}"
                                                class="text-xs font-semibold uppercase text-indigo-700 dark:text-indigo-400 no-underline hover:underline"
                                            >Modifier</a>
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
