@props([
    'danger' => false,
])

{{--
    Card of the pages: a surface whose children are spaced by gap, never by margins.
    The optional "title" and "description" slots make its header; "danger" adds a red ring.
--}}
<section
    {{
        $attributes->class([
            'flex', 'flex-col', 'gap-6',
            'rounded-md', 'bg-white', 'dark:bg-gray-800',
            'p-6', 'shadow-sm',
            'ring-2 ring-red-800 dark:ring-red-600' => $danger,
        ])
    }}
>
    @if (isset($title) || isset($description))
        <header class="flex flex-col gap-1">
            @isset($title)
                <h2 class="text-lg font-semibold">{{ $title }}</h2>
            @endisset

            @isset($description)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endisset
        </header>
    @endif

    {{ $slot }}
</section>
