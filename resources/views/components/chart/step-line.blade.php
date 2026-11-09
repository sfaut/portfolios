{{--
    Courbe en paliers de valeurs cumulées (App\View\Components\Chart\StepLine) : SVG étiré sur sa
    boîte, étiquettes de fin dans la marge de droite, mois en dessous. Les positions sont calculées
    par la classe ; la vue ne fait que les placer.

    @var string $label Description accessible de la courbe.
    @var list<array{points: string, line: string, dashed: bool, leader: string}> $polylines Tracés, de l'arrière-plan au premier plan.
    @var list<array{top: float, amount: string, caption: string, text: string}> $labels Étiquettes de fin.
    @var list<array{left: float, text: string, year: string, minor: bool}> $ticks Mois sous la courbe ; un écran étroit
        en masque l'année et les mois secondaires.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine.
--}}
<figure role="img" aria-label="{{ $label }}" {{ $attributes->class(['grid', 'grid-cols-[minmax(0,1fr)_7.5rem]', 'gap-x-4', 'gap-y-2']) }}>
    <div class="relative h-56 border-b border-gray-300 dark:border-gray-600" aria-hidden="true">
        <svg
            viewBox="0 0 {{ App\View\Components\Chart\StepLine::VIEWBOX_WIDTH }} {{ App\View\Components\Chart\StepLine::VIEWBOX_HEIGHT }}"
            preserveAspectRatio="none"
            class="absolute inset-0 size-full overflow-visible"
        >
            @foreach ($polylines as $polyline)
                @if ($polyline['leader'] !== '')
                    <polyline
                        points="{{ $polyline['leader'] }}"
                        fill="none"
                        stroke-dasharray="2 4"
                        vector-effect="non-scaling-stroke"
                        class="stroke-1 stroke-gray-300 dark:stroke-gray-600"
                    />
                @endif
                <polyline
                    points="{{ $polyline['points'] }}"
                    fill="none"
                    stroke-linejoin="round"
                    @if ($polyline['dashed']) stroke-dasharray="5 4" @endif
                    vector-effect="non-scaling-stroke"
                    class="{{ $polyline['line'] }}"
                />
            @endforeach
        </svg>
    </div>

    <div class="relative h-56" aria-hidden="true">
        @foreach ($labels as $chart_label)
            <div class="absolute left-0 -translate-y-1/2 flex flex-col leading-tight" style="top: {{ $chart_label['top'] }}%">
                <span class="{{ $chart_label['text'] }} font-semibold tabular-nums whitespace-nowrap">{{ $chart_label['amount'] }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $chart_label['caption'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="relative h-4 text-xs text-gray-500 dark:text-gray-400" aria-hidden="true">
        @foreach ($ticks as $tick)
            <span
                @class(['absolute', 'top-0', 'whitespace-nowrap', 'hidden sm:inline' => $tick['minor']])
                style="left: {{ $tick['left'] }}%"
            >{{ $tick['text'] }}@if ($tick['year'] !== '')<span class="hidden sm:inline"> {{ $tick['year'] }}</span>@endif</span>
        @endforeach
    </div>
</figure>
