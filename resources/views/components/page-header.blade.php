@props([
    'breadcrumb' => [],
])

{{--
    Bandeau d'une page, en trois rangs, chacun à un seul rôle et toujours à la même place ; un rang
    sans contenu est omis.
    - Au-dessus du titre, le fil d'Ariane : d'où vient la page, sur les pages de second niveau.
    - Le titre, qui nomme la page.
    - Sous le titre, le statut : une courte ligne grise sur l'état des données affichées
      (« 7 comptes enregistrés », « Au 14 novembre 2026 »), absente des formulaires.
    À droite, les actions qui portent sur toute la page, le secondaire avant le principal, qui finit
    au bord droit ; elles passent sous le titre sur un écran étroit. Une action qui sert un jeu de
    données (export, pagination) reste à côté de lui, dans le corps de la page.

    <x-page-header :breadcrumb="[route('portfolio.show', $portfolio) => 'Comptes']">
        Compte courant
        <x-slot:status>24 opérations enregistrées</x-slot:status>
        <x-slot:actions>…</x-slot:actions>
    </x-page-header>

    @var array<string, string> $breadcrumb Liens du fil d'Ariane (<x-breadcrumb>), vide sans fil.
    @var \Illuminate\View\ComponentSlot $slot Titre de la page, dans le <h2>.
    @var \Illuminate\View\ComponentSlot|null $status Statut, sous le titre.
    @var \Illuminate\View\ComponentSlot|null $actions Actions de la page, à droite.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du <h2>.
--}}
<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex min-w-0 flex-col gap-1">
        @if ($breadcrumb !== [])
            <x-breadcrumb :links="$breadcrumb"/>
        @endif
        <h2 {{ $attributes->class('text-xl font-semibold') }}>{{ $slot }}</h2>
        @isset($status)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $status }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
