{{--
    Chiffres d'une page, en tête de ses données : une ligne de <x-summary.item> à gauche (petit
    libellé gris, valeur en gras), et à droite les outils du jeu de données (export, « Tout »,
    pagination). Les chiffres d'une page sont ici, dans le corps, et jamais dans la description
    du bandeau : ils suivent ainsi les filtres.

    <x-summary>
        <x-summary.item label="Comptes" :of="$filtered ? $account_total : null">{{ $account_count }}</x-summary.item>
        <x-slot:tools>…</x-slot:tools>
    </x-summary>

    @var \Illuminate\View\ComponentSlot $slot Chiffres, en <x-summary.item>.
    @var \Illuminate\View\ComponentSlot|null $tools Outils du jeu de données, à droite.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine.
--}}
<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-x-8 gap-y-4') }}>
    <dl role="status" class="flex flex-wrap gap-x-10 gap-y-2">
        {{ $slot }}
    </dl>
    @isset($tools)
        <div class="flex flex-wrap items-center gap-3">{{ $tools }}</div>
    @endisset
</div>
