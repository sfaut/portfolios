{{--
    Carte cliquable d'une liste (portfolio, compte, axe analytique) : un lien sur toute la carte,
    fond blanc et bordure grise, dont la bordure passe en indigo au survol, avec un contour de focus
    au clavier. Son contenu s'empile en colonne, le dernier bloc calé en bas, pour que les cartes
    d'une même rangée s'alignent : ses mesures, dans le slot stats, chacune par <x-link-card.stat>.
    Une carte archivée est estompée.

    @var string $href Adresse de la page de l'élément.
    @var bool $archived Si l'élément est archivé.
    @var \Illuminate\View\ComponentSlot $slot Contenu de la carte : nom, description, montant…
    @var \Illuminate\View\ComponentSlot|null $stats Mesures de l'élément, en bas de la carte.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du lien.
--}}
@props([
    'href',
    'archived' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'flex', 'flex-col', 'justify-between', 'gap-3',
        'rounded-md', 'border', 'border-gray-200', 'dark:border-gray-700',
        'bg-white', 'dark:bg-gray-800', 'p-4', 'shadow-sm',
        'hover:border-indigo-500', 'dark:hover:border-indigo-400', 'hover:shadow-md',
        'focus-visible:outline-2', 'focus-visible:outline-offset-2', 'focus-visible:outline-indigo-500',
        'transition', 'duration-150', 'ease-linear',
        'opacity-75' => $archived,
    ]) }}
>
    {{ $slot }}
    @isset($stats)
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">{{ $stats }}</div>
    @endisset
</a>
