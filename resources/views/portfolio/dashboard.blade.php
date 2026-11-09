{{--
    Tableau de bord d'un portfolio, sa page d'accueil : chiffres clefs à la date du jour, puis ventes de
    la période choisie (courbe des ventes cumulées, ventes par mois, répartition analytique).

    Une vente est une opération de montant négatif, affichée en valeur positive (App\Support\PortfolioSales).

    @var \App\Models\Portfolio $portfolio Portfolio affiché.
    @var \Carbon\CarbonImmutable $today Date des chiffres clefs.
    @var int $active_accounts Nombre de comptes actifs.
    @var int $archived_accounts Nombre de comptes archivés.
    @var \App\Support\SalesComparison $current_month Ventes du mois en cours, à date.
    @var \App\Support\SalesComparison $previous_month Ventes du mois précédent.
    @var \App\Support\SalesComparison $last_twelve_months Ventes des douze derniers mois complets.
    @var array{amount: float, accounts: int} $outstanding Reste à encaisser et nombre de comptes concernés.
    @var \App\Support\MonthRestriction $restriction Mois choisissables pour la période.
    @var array{start: string, end: string}|null $period Période choisie, null sans opération.
    @var \App\Support\SalesDashboard|null $dashboard Ventes de la période, null sans opération.
    @var \App\Support\SalesComparison|null $comparison Total de la période, comparé à un an plus tôt.
    @var array{current: array{points: list<array{0: int, 1: float}>, end: int}, previous: array{points: list<array{0: int, 1: float}>, end: int}}|null $cumulative Ventes cumulées.
    @var list<array<string, mixed>> $months Ventes par mois de la période (SalesDashboard::monthly()).
    @var list<array<string, mixed>> $axes Répartition analytique de la période (SalesDashboard::byAnalyticAxis()).
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Tableau de bord</h2>
        <div class="text-sm text-gray-500 dark:text-gray-400">Chiffres au {{ $today->translatedFormat('j F Y') }}</div>
    </x-slot>

    <div class="max-w-6xl mx-auto flex flex-col gap-6">

        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-px overflow-hidden rounded-md bg-gray-200 dark:bg-gray-700 shadow-sm">
            <div class="flex flex-col gap-1 bg-white dark:bg-gray-800 p-5">
                <dt class="order-2 text-sm text-gray-600 dark:text-gray-300">{{ trans_choice('{0,1} compte actif|[2,*] comptes actifs', $active_accounts) }}</dt>
                <dd class="order-1 text-2xl font-semibold tabular-nums">{{ Number::format($active_accounts, locale: 'fr') }}</dd>
                <dd class="order-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ trans_choice('{0} aucun compte archivé|{1} 1 compte archivé|[2,*] :count comptes archivés', $archived_accounts) }}
                </dd>
            </div>

            @foreach ([$current_month, $previous_month, $last_twelve_months] as $figure)
                <div class="flex flex-col gap-1 bg-white dark:bg-gray-800 p-5">
                    <dt class="order-2 text-sm text-gray-600 dark:text-gray-300">{{ $figure->label }}</dt>
                    <dd class="order-1 text-2xl font-semibold tabular-nums whitespace-nowrap">{{ Number::amount($figure->amount, currency: 'EUR') }}</dd>
                    <dd class="order-3 text-xs text-gray-500 dark:text-gray-400">{{ $figure->sentence() }}</dd>
                </div>
            @endforeach

            <div class="flex flex-col gap-1 bg-white dark:bg-gray-800 p-5 sm:col-span-2 lg:col-span-1">
                <dt class="order-2 text-sm text-gray-600 dark:text-gray-300">reste à encaisser</dt>
                <dd class="order-1 text-2xl font-semibold tabular-nums whitespace-nowrap">{{ Number::amount($outstanding['amount'], currency: 'EUR') }}</dd>
                <dd class="order-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ trans_choice('{0} tout est encaissé|{1} sur 1 compte|[2,*] sur :count comptes', $outstanding['accounts']) }}
                </dd>
            </div>
        </dl>

        @if ($dashboard === null)
            <x-card>
                <x-slot:title>Pas encore de vente</x-slot:title>
                <x-slot:description>
                    Les ventes et les paiements se saisissent dans les comptes du portfolio. Le tableau de bord
                    les suivra dès la première opération.
                </x-slot:description>
                <x-slot:footer>
                    <x-link.primary :target="route('portfolio.show', $portfolio)">Voir les comptes</x-link.primary>
                </x-slot:footer>
            </x-card>
        @else
            <section aria-labelledby="period-sales" class="flex flex-col gap-6 rounded-md bg-white dark:bg-gray-800 p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <h3 id="period-sales" class="text-2xl font-semibold">
                            <span class="tabular-nums whitespace-nowrap">{{ Number::amount($comparison->amount, currency: 'EUR') }}</span>
                            de ventes {{ $comparison->label }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ Str::ucfirst($comparison->sentence()) }}.</p>
                    </div>

                    <form
                        method="get"
                        action="{{ route('portfolio.dashboard', $portfolio) }}"
                        x-data="{ period: @js($period) }"
                        x-init="$watch('period', () => $nextTick(() => $el.requestSubmit()))"
                        class="flex items-center gap-2"
                    >
                        <x-month-picker
                            name="period"
                            label="Période"
                            mode="range"
                            :value="$period"
                            :restriction="$restriction"
                            placeholder="Période par défaut"
                            x-model="period"
                        />
                        <noscript>
                            <button type="submit" class="text-sm text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Afficher</button>
                        </noscript>
                    </form>
                </div>

                @if ($comparison->amount > 0)
                    <x-chart.step-line
                        :from="$dashboard->from"
                        :days="$dashboard->days()"
                        :series="array_filter([
                            [
                                ...$cumulative['current'],
                                'caption' => $dashboard->yearsLabel(),
                                'line' => 'stroke-[2.5] stroke-indigo-600 dark:stroke-indigo-400',
                                'text' => 'text-indigo-700 dark:text-indigo-300',
                            ],
                            $comparison->previous === null ? null : [
                                ...$cumulative['previous'],
                                'caption' => $dashboard->yearsLabel(1),
                                'line' => 'stroke-[1.5] stroke-gray-400 dark:stroke-gray-500',
                                'text' => 'text-gray-500 dark:text-gray-400',
                                'dashed' => true,
                            ],
                        ])"
                        :label="'Ventes cumulées '.$comparison->label.' : '.Number::amount($comparison->amount, currency: 'EUR').($comparison->previous === null ? '' : ', contre '.Number::amount($comparison->previous, currency: 'EUR').' sur '.$comparison->reference)"
                    />
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Aucune vente {{ $comparison->label }}. Choisissez une autre période.
                    </p>
                @endif
            </section>

            @if ($comparison->amount > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <x-card>
                        <x-slot:title>Ventes par mois</x-slot:title>
                        <x-slot:description>Survolez un mois pour le détail des ventes et des paiements.</x-slot:description>

                        <div class="flex flex-col gap-2">
                            <div class="flex h-40 items-end gap-1 border-b border-gray-300 dark:border-gray-600">
                                @foreach ($months as $month)
                                    <x-tooltip
                                        role="img"
                                        aria-label="{{ $month['label'] }} : {{ Number::amount($month['sales'], currency: 'EUR') }}"
                                        class="h-full min-w-0 flex-1 items-end"
                                    >
                                        <span
                                            class="block w-full rounded-t-sm bg-indigo-600 dark:bg-indigo-400"
                                            style="height: {{ round($month['ratio'] * 100, 2) }}%"
                                        ></span>
                                        <x-slot:content>
                                            <span class="block font-semibold">{{ $month['label'] }}</span>
                                            <span class="block">{{ Number::amount($month['sales'], currency: 'EUR') }} de ventes ({{ trans_choice('{0} aucune vente|{1} 1 vente|[2,*] :count ventes', $month['count']) }})</span>
                                            <span class="block">{{ Number::amount($month['payments'], currency: 'EUR') }} de paiements</span>
                                        </x-slot:content>
                                    </x-tooltip>
                                @endforeach
                            </div>
                            <div class="flex gap-1 text-xs text-gray-500 dark:text-gray-400" aria-hidden="true">
                                @foreach ($months as $month)
                                    <span class="flex min-w-0 flex-1 flex-col items-center">
                                        <span>{{ $month['initial'] }}</span>
                                        <span class="h-4 whitespace-nowrap">{{ $month['year'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <details class="text-sm">
                            <summary class="cursor-pointer text-indigo-700 dark:text-indigo-400">Voir les chiffres</summary>
                            <table class="mt-3 w-full tabular-nums">
                                <thead class="text-left text-xs text-gray-500 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="py-1 font-medium">Mois</th>
                                        <th scope="col" class="py-1 text-right font-medium">Ventes</th>
                                        <th scope="col" class="py-1 text-right font-medium">Nombre</th>
                                        <th scope="col" class="py-1 text-right font-medium">Paiements</th>
                                        <th scope="col" class="py-1 text-right font-medium">Cumul des ventes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($months as $month)
                                        <tr>
                                            <th scope="row" class="py-1 text-left font-normal">{{ $month['label'] }}</th>
                                            <td class="py-1 text-right">{{ Number::amount($month['sales']) }}</td>
                                            <td class="py-1 text-right">{{ $month['count'] }}</td>
                                            <td class="py-1 text-right">{{ Number::amount($month['payments']) }}</td>
                                            <td class="py-1 text-right">{{ Number::amount($month['cumulative']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </details>
                    </x-card>

                    <x-card>
                        <x-slot:title>Répartition analytique</x-slot:title>
                        <x-slot:description>Part des ventes de la période attribuée à chaque axe.</x-slot:description>

                        <ul class="flex flex-col gap-3">
                            @foreach ($axes as $axis)
                                <li class="grid grid-cols-[minmax(0,9rem)_minmax(0,1fr)_auto] items-center gap-3 text-sm">
                                    <span @class(['truncate', 'text-gray-500 dark:text-gray-400' => $axis['unallocated']])>
                                        {{ $axis['name'] }}
                                        @if ($axis['archived'])
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(archivé)</span>
                                        @endif
                                    </span>
                                    <span class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-700" aria-hidden="true">
                                        <span
                                            @class([
                                                'block', 'h-full', 'rounded-full',
                                                'bg-indigo-600 dark:bg-indigo-400' => ! $axis['unallocated'],
                                                'bg-gray-400 dark:bg-gray-500' => $axis['unallocated'],
                                            ])
                                            style="width: {{ round($axis['share'] * 100, 2) }}%"
                                        ></span>
                                    </span>
                                    <span class="flex justify-end gap-3 tabular-nums whitespace-nowrap">
                                        <span>{{ Number::amount($axis['amount'], currency: 'EUR') }}</span>
                                        <span class="w-10 text-right text-gray-500 dark:text-gray-400">{{ $axis['percent'] }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        @if (count($axes) === 1 && $axes[0]['unallocated'])
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Aucune vente de la période n'est répartie : la répartition se fait dans le formulaire d'une opération,
                                sur les <a href="{{ route('analytic-axis.index', $portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">axes analytiques</a> du portfolio.
                            </p>
                        @endif
                    </x-card>
                </div>
            @endif
        @endif

    </div>
</x-app-layout>
