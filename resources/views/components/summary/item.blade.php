@props([
    'label',
    'of' => null,
])

{{--
    Un chiffre de <x-summary> : son libellé, puis sa valeur, suivie de « sur N » quand un filtre
    réduit la liste.

    @var string $label Libellé du chiffre (« Comptes », « Solde »).
    @var int|null $of Total sans filtre, null quand aucun filtre ne réduit la liste.
    @var \Illuminate\View\ComponentSlot $slot Valeur, déjà mise en forme.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la valeur (couleur d'un solde…).
--}}
<div class="flex flex-col">
    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt>
    <dd {{ $attributes->class('text-lg font-semibold tabular-nums') }}>
        {{ $slot }}
        @if ($of !== null)
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">sur {{ Number::format($of, locale: 'fr') }}</span>
        @endif
    </dd>
</div>
