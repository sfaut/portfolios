{{--
    En-tête de colonne triable (App\View\Components\SortableHeading) : le lien trie le tableau sur la
    colonne, ou inverse le sens du tri en cours. La colonne triée ressort en indigo, avec une flèche
    dans le sens du tri.

    @var bool $active Si le tableau est trié sur cette colonne.
    @var string $ariaSort Valeur d'aria-sort : ascending, descending ou none.
    @var string $icon Icône Lucide de l'état du tri.
    @var string $url Adresse du tri suivant.
    @var string $hint Ce que fait le lien, pour les lecteurs d'écran.
    @var string $align Alignement du contenu : left, center ou right.
    @var \Illuminate\View\ComponentSlot $slot Libellé de la colonne.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du `<th>`.
--}}
<th scope="col" aria-sort="{{ $ariaSort }}" {{ $attributes->class(['border', 'p-0']) }}>
    <a
        href="{{ $url }}"
        data-fragment-link
        @class([
            'flex', 'items-center', 'gap-1', 'px-3', 'py-2', 'whitespace-nowrap', 'no-underline',
            'hover:bg-gray-200', 'dark:hover:bg-gray-600',
            'focus-visible:outline-2', 'focus-visible:-outline-offset-2', 'focus-visible:outline-indigo-500',
            'justify-start' => $align === 'left',
            'justify-center' => $align === 'center',
            'justify-end' => $align === 'right',
            'text-indigo-700 dark:text-indigo-300' => $active,
        ])
    >
        <span>{{ $slot }}</span>
        <x-dynamic-component
            :component="'lucide-'.$icon"
            @class(['size-3.5', 'shrink-0', 'text-gray-400 dark:text-gray-500' => ! $active])
            aria-hidden="true"
        />
        <span class="sr-only">, {{ $hint }}</span>
    </a>
</th>
