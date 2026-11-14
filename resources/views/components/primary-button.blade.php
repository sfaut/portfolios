{{--
    Bouton principal, d'envoi par défaut.

    @var string|null $icon Nom de l'icône Lucide placée devant le texte (https://lucide.dev/icons), null sans icône.
    @var \Illuminate\View\ComponentSlot $slot Texte du bouton.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du bouton.
--}}
@props([
    'icon' => null,
])

@php

$attributes = $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center px-4 py-2 '
        . 'bg-gray-800 dark:bg-indigo-500 '
        . 'rounded-md font-semibold text-xs text-white '
        . 'uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-indigo-400 '
        . 'focus:bg-gray-700 dark:focus:bg-indigo-400 active:bg-gray-900 dark:active:bg-indigo-600 '
        . 'focus:outline-hidden focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-300 '
        . 'focus:ring-offset-2 dark:focus:ring-offset-gray-800 '
        . 'disabled:opacity-25 disabled:pointer-events-none '
        . 'transition ease-in-out duration-150'
        . ($icon !== null ? ' gap-2' : ''),
]);

@endphp

<button {{ $attributes }}>@if ($icon !== null)<x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>@endif{{ $slot }}</button>
