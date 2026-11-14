{{--
    Bouton secondaire, simple bouton par défaut.

    @var string|null $icon Nom de l'icône Lucide placée devant le texte (https://lucide.dev/icons), null sans icône.
    @var \Illuminate\View\ComponentSlot $slot Texte du bouton.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du bouton.
--}}
@props([
    'icon' => null,
])

<button {{ $attributes->merge(['type' => 'button'])->class(['inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-xs hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150', 'gap-2' => $icon !== null]) }}>
    @if ($icon !== null)
        <x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>
    @endif
    {{ $slot }}
</button>
