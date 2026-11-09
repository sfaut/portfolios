@props([
    'label',
])

{{--
    Navigation en contrôle segmenté entre des pages sœurs : une bande grise qui porte les liens
    (<x-segmented-nav.item>), la page courante en relief.

    <x-segmented-nav label="Pages du portfolio">
        <x-segmented-nav.item :href="route('portfolio.dashboard', $portfolio)" current>Tableau de bord</x-segmented-nav.item>
        <x-segmented-nav.item :href="route('portfolio.show', $portfolio)">Comptes</x-segmented-nav.item>
    </x-segmented-nav>

    @var string $label Nom accessible de la navigation (aria-label), qui la distingue de la navigation principale.
    @var \Illuminate\View\ComponentSlot $slot Liens, des <x-segmented-nav.item>.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine.
--}}
<nav aria-label="{{ $label }}" {{ $attributes->class(['flex', 'gap-1', 'rounded-md', 'bg-gray-200', 'dark:bg-gray-700', 'p-1']) }}>
    {{ $slot }}
</nav>
