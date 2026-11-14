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
        'bg-primary-800', 'dark:bg-primary-500',
        'px-4', 'py-2',
        'text-white', 'text-xs', 'font-semibold', 'uppercase', 'tracking-widest',
        "hover:bg-primary-700", 'dark:hover:bg-primary-400',
        'active:ring-2', 'active:ring-primary-700', 'dark:active:ring-primary-300', 'active:ring-offset-2', 'dark:active:ring-offset-gray-900',
        'transition-all', 'duration-100', 'ease-linear',
    ]) }}

>@if ($icon !== null)<x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>@endif{{ $slot }}</a>
