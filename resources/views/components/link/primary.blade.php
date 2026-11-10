@props([
    'target',
])

<a
    href="{{ $target }}"

    @class([
        'rounded-md',
        'bg-primary-800', 'dark:bg-primary-500',
        'px-4', 'py-2',
        'text-white', 'text-xs', 'font-semibold', 'uppercase', 'tracking-widest',
        "hover:bg-primary-700", 'dark:hover:bg-primary-400',
        'active:ring-2', 'active:ring-primary-700', 'dark:active:ring-primary-300', 'active:ring-offset-2', 'dark:active:ring-offset-gray-900',
        'transition-all', 'duration-100', 'ease-linear',
    ])

>{{ $slot }}</a>
