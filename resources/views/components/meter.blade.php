@props([
    'value',
    'level' => 'ok',
])

{{--
    Jauge fine d'une mesure à remplir (mémoire, disque…), colorée selon son niveau. Elle double le
    texte de la mesure, seul lu par les lecteurs d'écran.

    @var float $value Taux de remplissage, de 0 à 1 (ramené dans ces bornes).
    @var string $level Niveau : ok, warning ou danger.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la jauge.
--}}
<div aria-hidden="true" {{ $attributes->class(['h-1.5', 'w-full', 'max-w-xs', 'overflow-hidden', 'rounded-full', 'bg-gray-200', 'dark:bg-gray-700']) }}>
    <div
        @class([
            'h-full', 'rounded-full',
            'bg-indigo-500 dark:bg-indigo-400' => $level === 'ok',
            'bg-amber-500' => $level === 'warning',
            'bg-red-600 dark:bg-red-500' => $level === 'danger',
        ])
        style="width: {{ round(min(1, max(0, $value)) * 100, 1) }}%"
    ></div>
</div>
