@props([
    'portfolio',
    'current',
])

{{--
    Navigation entre les pages d'un portfolio : tableau de bord, comptes et axes analytiques, en
    contrôle segmenté (<x-segmented-nav>), dans la barre de navigation. Sur un écran étroit, elle
    prend toute la largeur, ses liens à parts égales.

    @var \App\Models\Portfolio $portfolio Portfolio des pages.
    @var string $current Section courante : dashboard, accounts, analytic-axes, ou '' sans section.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la navigation.
--}}
<x-segmented-nav label="Pages du portfolio" {{ $attributes->class(['w-full', 'sm:w-auto']) }}>
    <x-segmented-nav.item class="flex-1 text-center sm:flex-none" :href="route('portfolio.dashboard', $portfolio)" :current="$current === 'dashboard'">Tableau de bord</x-segmented-nav.item>
    <x-segmented-nav.item class="flex-1 text-center sm:flex-none" :href="route('portfolio.show', $portfolio)" :current="$current === 'accounts'">Comptes</x-segmented-nav.item>
    <x-segmented-nav.item class="flex-1 text-center sm:flex-none" :href="route('analytic-axis.index', $portfolio)" :current="$current === 'analytic-axes'">Axes analytiques</x-segmented-nav.item>
</x-segmented-nav>
