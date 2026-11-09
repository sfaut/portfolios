@props([
    'target',
])

<a
    href="{{ $target }}"

    {{ $attributes->class([
        'rounded-md',
        'bg-secondary-300', 'dark:bg-secondary-700',
        'px-4', 'py-2',
        'text-gray-800', 'dark:text-gray-100', 'text-xs', 'font-semibold', 'uppercase', 'tracking-widest',
        "hover:bg-secondary-400", 'dark:hover:bg-secondary-600',
        'active:ring-2', 'active:ring-secondary-400', 'active:ring-offset-2', 'dark:active:ring-offset-gray-800',
        'transition-all', 'duration-100', 'ease-linear',
    ]) }}

>{{ $slot }}</a>
