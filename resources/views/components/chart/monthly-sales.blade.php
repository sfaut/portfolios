{{--
    Ventes par mois, fantômes un an plus tôt et cumul de la période (App\View\Components\Chart\MonthlySales) :
    axe des ventes du mois à gauche (indigo), axe du cumul à droite (ambre), lignes de repère partagées,
    mois en dessous, légende. Chaque colonne a son infobulle, et la classe calcule toutes les positions.

    @var list<array{percent: float, left: string, right: string}> $ticks Graduations des deux axes.
    @var list<array<string, mixed>> $columns Colonnes, une par mois, avec leurs positions.
    @var string $line Points de la courbe du cumul, boîte de 100 × 100.
    @var string $years Année ou années de la période.
    @var string $previousYears Année ou années un an plus tôt.
    @var string $label Description accessible du graphe.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine.
--}}
<figure aria-label="{{ $label }}" {{ $attributes->class(['flex', 'flex-col', 'gap-3']) }}>
    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400" aria-hidden="true">
        <span>€ par mois</span>
        <span>€ cumulés</span>
    </div>

    <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] gap-x-2">
        <div class="relative h-56 text-right text-xs tabular-nums text-indigo-700 dark:text-indigo-300" aria-hidden="true">
            @foreach ($ticks as $tick)
                <span class="invisible block h-0 whitespace-nowrap">{{ $tick['left'] }}</span>
                <span class="absolute right-0 translate-y-1/2 whitespace-nowrap" style="bottom: {{ $tick['percent'] }}%">{{ $tick['left'] }}</span>
            @endforeach
        </div>

        <div class="relative h-56">
            @foreach ($ticks as $tick)
                <span
                    @class([
                        'absolute inset-x-0 border-t',
                        'border-gray-300 dark:border-gray-600' => $loop->first,
                        'border-dashed border-gray-200 dark:border-gray-700' => ! $loop->first,
                    ])
                    style="bottom: {{ $tick['percent'] }}%"
                    aria-hidden="true"
                ></span>
            @endforeach

            <div class="absolute inset-0 flex">
                @foreach ($columns as $column)
                    <x-tooltip
                        role="img"
                        aria-label="{{ $column['label'] }} : {{ Number::amount($column['sales'], currency: 'EUR') }}"
                        class="h-full min-w-0 flex-1 items-end justify-center rounded-sm"
                    >
                        {{-- La barre est centrée dans sa colonne, sous le point du cumul ; le fantôme s'accroche à sa gauche. --}}
                        <span class="relative flex h-full w-1/2 max-w-7 items-end">
                            <span
                                class="absolute right-full bottom-0 mr-px w-1 sm:w-1.5 rounded-t-sm bg-gray-300 dark:bg-gray-600"
                                style="height: {{ $column['ghost'] }}%"
                            ></span>
                            <span
                                class="block w-full rounded-t-sm bg-[color-mix(in_oklab,var(--color-indigo-700)_var(--shade),var(--color-indigo-300))] dark:bg-[color-mix(in_oklab,var(--color-indigo-300)_var(--shade),var(--color-indigo-700))]"
                                style="height: {{ $column['height'] }}%; --shade: {{ $column['shade'] }}%"
                            ></span>
                        </span>
                        <x-slot:content>
                            <span class="block font-semibold">{{ $column['label'] }}</span>
                            <span class="block">{{ Number::amount($column['sales'], currency: 'EUR') }} de ventes ({{ trans_choice('{0} aucune vente|{1} 1 vente|[2,*] :count ventes', $column['count']) }})</span>
                            <span class="block">{{ Number::amount($column['previous'], currency: 'EUR') }} un an plus tôt</span>
                            <span class="block">{{ Number::amount($column['payments'], currency: 'EUR') }} de paiements</span>
                            <span class="block">{{ Number::amount($column['cumulative'], currency: 'EUR') }} cumulés depuis le début de la période</span>
                        </x-slot:content>
                    </x-tooltip>
                @endforeach
            </div>

            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="pointer-events-none absolute inset-0 size-full overflow-visible" aria-hidden="true">
                <polyline
                    points="{{ $line }}"
                    fill="none"
                    stroke-linejoin="round"
                    vector-effect="non-scaling-stroke"
                    class="stroke-2 stroke-amber-500 dark:stroke-amber-400"
                />
            </svg>

            @foreach ($columns as $column)
                <span
                    class="pointer-events-none absolute size-2 -translate-x-1/2 translate-y-1/2 rounded-full bg-amber-500 dark:bg-amber-400 ring-2 ring-white dark:ring-gray-800"
                    style="left: {{ $column['x'] }}%; bottom: {{ $column['y'] }}%"
                    aria-hidden="true"
                ></span>
            @endforeach
        </div>

        <div class="relative h-56 text-left text-xs tabular-nums text-amber-700 dark:text-amber-300" aria-hidden="true">
            @foreach ($ticks as $tick)
                <span class="invisible block h-0 whitespace-nowrap">{{ $tick['right'] }}</span>
                <span class="absolute left-0 translate-y-1/2 whitespace-nowrap" style="bottom: {{ $tick['percent'] }}%">{{ $tick['right'] }}</span>
            @endforeach
        </div>

        <div></div>
        <div class="flex pt-1 text-xs text-gray-500 dark:text-gray-400" aria-hidden="true">
            @foreach ($columns as $column)
                <span class="flex min-w-0 flex-1 flex-col items-center">
                    <span>{{ $column['initial'] }}</span>
                    <span class="h-4 whitespace-nowrap">{{ $column['year'] }}</span>
                </span>
            @endforeach
        </div>
        <div></div>
    </div>

    <figcaption class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
        <span class="flex items-center gap-1.5">
            <span class="size-2.5 rounded-sm bg-indigo-600 dark:bg-indigo-400" aria-hidden="true"></span>
            Ventes {{ $years }}
        </span>
        <span class="flex items-center gap-1.5">
            <span class="size-2.5 rounded-sm bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>
            Ventes {{ $previousYears }}, même mois
        </span>
        <span class="flex items-center gap-1.5">
            <span class="h-0.5 w-3 rounded-full bg-amber-500 dark:bg-amber-400" aria-hidden="true"></span>
            Cumul {{ $years }}
        </span>
    </figcaption>
</figure>
