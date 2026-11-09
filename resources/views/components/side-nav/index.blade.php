@props([
    'label',
])

{{--
    Navigation latérale entre les sections d'une page : une colonne de liens (<x-side-nav.item>) sur
    grand écran, une bande horizontale défilante sur un écran étroit. Le lien courant porte un filet
    indigo, à gauche en colonne, dessous en bande.

    <x-side-nav label="Sections du relevé" x-data="sectionSpy">
        <x-side-nav.item href="#php" status="warning">PHP</x-side-nav.item>
    </x-side-nav>

    Le composant Alpine sectionSpy, posé sur la racine, marque le lien de la section visible au
    défilement.

    @var string $label Nom accessible de la navigation (aria-label), qui la distingue de la navigation principale.
    @var \Illuminate\View\ComponentSlot $slot Liens, des <x-side-nav.item>.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine.
--}}
<nav aria-label="{{ $label }}" {{ $attributes->class(['min-w-0']) }}>
    <ul
        @class([
            'flex', 'overflow-x-auto',
            'border-b', 'border-gray-200', 'dark:border-gray-700',
            'lg:flex-col', 'lg:overflow-visible',
            'lg:border-b-0', 'lg:border-l',
        ])
    >
        {{ $slot }}
    </ul>
</nav>
