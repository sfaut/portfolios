{{--
    Lien à l'allure du bouton principal.

    @var string $target Adresse du lien.
    @var string|null $icon Nom de l'icône Lucide placée devant le texte (https://lucide.dev/icons), null sans icône.
    @var \Illuminate\View\ComponentSlot $slot Texte du lien.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du lien.
--}}
@props([
    'target',
    'icon' => null,
])

<a
    href="{{ $target }}"

    {{ $attributes->class([
        'inline-flex items-center gap-2' => $icon !== null,
        'rounded-md',
        'bg-indigo-600', 'dark:bg-indigo-500',
        'px-4', 'py-2',
        'text-white', 'text-xs', 'font-semibold', 'uppercase', 'tracking-widest',
        'hover:bg-indigo-500', 'dark:hover:bg-indigo-400',
        'focus:bg-indigo-500', 'dark:focus:bg-indigo-400', 'active:bg-indigo-700', 'dark:active:bg-indigo-600',
        'focus:outline-hidden', 'focus:ring-2', 'focus:ring-indigo-500', 'dark:focus:ring-indigo-300',
        'focus:ring-offset-2', 'dark:focus:ring-offset-gray-800',
        'transition', 'ease-in-out', 'duration-150',
    ]) }}

>@if ($icon !== null)<x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>@endif{{ $slot }}</a>
