@props([
    'portfolio',
    'current',
])

{{--
    Navigation entre les pages d'un portfolio : tableau de bord, comptes et axes analytiques, en
    contrôle segmenté (<x-segmented-nav>).

    @var \App\Models\Portfolio $portfolio Portfolio des pages.
    @var string $current Page courante : dashboard, accounts ou analytic-axes.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la navigation.
--}}
<x-segmented-nav label="Pages du portfolio" {{ $attributes }}>
    <x-segmented-nav.item :href="route('portfolio.dashboard', $portfolio)" :current="$current === 'dashboard'">Tableau de bord</x-segmented-nav.item>
    <x-segmented-nav.item :href="route('portfolio.show', $portfolio)" :current="$current === 'accounts'">Comptes</x-segmented-nav.item>
    <x-segmented-nav.item :href="route('analytic-axis.index', $portfolio)" :current="$current === 'analytic-axes'">Axes analytiques</x-segmented-nav.item>
</x-segmented-nav>
