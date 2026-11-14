{{--
    Champ d'une barre de filtres (<x-filter-bar>) : un petit libellé gris au-dessus de son contrôle.
    Avec `for`, le libellé est le <label> du champ qui porte cet id ; sans, il est masqué aux lecteurs
    d'écran, le contrôle portant déjà son nom (sélecteurs de mois, de dates, d'options).

    @var string $label Libellé affiché.
    @var string $for Id du champ que le libellé désigne ; vide pour un contrôle qui porte son nom.
    @var \Illuminate\View\ComponentSlot $slot Contrôle du champ.
--}}
@props([
    'label',
    'for' => '',
])

<div {{ $attributes->class('flex flex-col gap-1') }}>
    @if ($for !== '')
        <label for="{{ $for }}" class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</label>
    @else
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400" aria-hidden="true">{{ $label }}</span>
    @endif

    {{ $slot }}
</div>
