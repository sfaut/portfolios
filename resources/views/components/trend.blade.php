@props([
    'comparison',
    'capitalize' => false,
])

{{--
    Comparaison d'un total de ventes à sa référence (un an plus tôt) : icône et évolution en couleur,
    suivies de la référence en gris (« ↗ +8 % sur octobre 2025 »). Hausse en vert (trending-up),
    baisse en rouge (trending-down), stable en gris (move-right) ; l'icône est masquée aux lecteurs
    d'écran, qui lisent l'évolution en toutes lettres (« en hausse de 8 % »). Sans vente dans la
    référence, la phrase seule : « aucune vente sur octobre 2025 ».

    @var \App\Support\SalesComparison $comparison Total comparé.
    @var bool $capitalize Majuscule en tête de la phrase sans comparaison (début de phrase).
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine.
--}}
<span {{ $attributes->class(['inline-flex flex-wrap items-center gap-x-1']) }}>
    @if ($comparison->trend() === null)
        {{ $capitalize ? Str::ucfirst($comparison->sentence()) : $comparison->sentence() }}
    @else
        <span @class([
            'inline-flex items-center gap-0.5 font-semibold tabular-nums whitespace-nowrap',
            'text-emerald-700 dark:text-emerald-400' => $comparison->trend() === 'up',
            'text-rose-700 dark:text-rose-400' => $comparison->trend() === 'down',
            'text-gray-600 dark:text-gray-300' => $comparison->trend() === 'flat',
        ])>
            @if ($comparison->trend() === 'up')
                <x-lucide-trending-up class="size-3.5 shrink-0" aria-hidden="true"/>
            @elseif ($comparison->trend() === 'down')
                <x-lucide-trending-down class="size-3.5 shrink-0" aria-hidden="true"/>
            @else
                <x-lucide-move-right class="size-3.5 shrink-0" aria-hidden="true"/>
            @endif
            <span aria-hidden="true">{{ $comparison->change() }}</span>
            <span class="sr-only">{{ $comparison->spokenChange() }}</span>
        </span>
        <span>sur {{ $comparison->reference }}</span>
    @endif
</span>
