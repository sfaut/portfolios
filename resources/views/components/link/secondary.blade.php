{{--
    Lien à l'allure du bouton secondaire.

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
        'bg-secondary-300', 'dark:bg-secondary-700',
        'px-4', 'py-2',
        'text-gray-800', 'dark:text-gray-200', 'text-xs', 'font-semibold', 'uppercase', 'tracking-widest',
        "hover:bg-secondary-400", 'dark:hover:bg-secondary-600',
        'active:ring-2', 'active:ring-secondary-400', 'active:ring-offset-2', 'dark:active:ring-offset-gray-800',
        'transition-all', 'duration-100', 'ease-linear',
    ]) }}

>@if ($icon !== null)<x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>@endif{{ $slot }}</a>
