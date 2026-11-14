@props([
    'breadcrumb' => [],
])

{{--
    Bandeau d'une page, en trois rangs, chacun à un seul rôle et toujours à la même place ; un rang
    sans contenu est omis.
    - Au-dessus du titre, le fil d'Ariane : d'où vient la page, sur les pages de second niveau.
    - Le titre, qui nomme la page.
    - Sous le titre, la description : une phrase grise qui dit ce que montre la page
      (« Les 7 comptes actifs du portfolio, avec leur solde… »), absente des formulaires.
    À droite, les actions qui portent sur toute la page, le secondaire avant le principal, qui finit
    au bord droit ; elles passent sous le titre sur un écran étroit. Une action qui sert un jeu de
    données (export, pagination) reste à côté de lui, dans le corps de la page.

    <x-page-header :breadcrumb="[route('portfolio.show', $portfolio) => 'Comptes']">
        Compte courant
        <x-slot:description>Les 24 opérations du compte, avec leur solde cumulé.</x-slot:description>
        <x-slot:actions>…</x-slot:actions>
    </x-page-header>

    @var array<string, string> $breadcrumb Liens du fil d'Ariane (<x-breadcrumb>), vide sans fil.
    @var \Illuminate\View\ComponentSlot $slot Titre de la page, dans le <h2>.
    @var \Illuminate\View\ComponentSlot|null $description Description de la page, sous le titre.
    @var \Illuminate\View\ComponentSlot|null $actions Actions de la page, à droite.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du <h2>.
--}}
<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex min-w-0 flex-col gap-1">
        @if ($breadcrumb !== [])
            <x-breadcrumb :links="$breadcrumb"/>
        @endif
        <h2 {{ $attributes->class('text-xl font-semibold') }}>{{ $slot }}</h2>
        @isset($description)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
