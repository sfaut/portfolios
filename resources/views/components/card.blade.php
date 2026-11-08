@props([
    'danger' => false,
])

{{--
    Card of the pages, in three sections separated by rules: a header (optional "title" and
    "description" slots), a body (the default slot, left out when empty) and a footer (optional
    "footer" slot, for the actions). A button of the footer reaches a form of the body through
    its "form" attribute. "danger" adds a red ring and a red title. The attributes of the "title"
    and "description" slots go to their element (an "id", for instance).
--}}
<section
    {{
        $attributes->class([
            'flex', 'flex-col', 'overflow-hidden',
            'divide-y', 'divide-gray-200', 'dark:divide-gray-700',
            'rounded-md', 'bg-white', 'dark:bg-gray-800', 'shadow-sm',
            'ring-2 ring-red-800 dark:ring-red-600' => $danger,
        ])
    }}
>
    @if (isset($title) || isset($description))
        <header class="flex flex-col gap-1 px-6 py-5">
            @isset($title)
                <h2
                    {{
                        $title->attributes->class([
                            'text-lg', 'font-semibold',
                            'text-red-800 dark:text-red-400' => $danger,
                        ])
                    }}
                >{{ $title }}</h2>
            @endisset

            @isset($description)
                <p {{ $description->attributes->class(['text-sm', 'text-gray-500', 'dark:text-gray-400']) }}>{{ $description }}</p>
            @endisset
        </header>
    @endif

    @if ($slot->hasActualContent())
        <div class="flex flex-col gap-6 p-6">
            {{ $slot }}
        </div>
    @endif

    @isset($footer)
        <footer
            {{
                $footer->attributes->class([
                    'flex', 'flex-wrap', 'items-center', 'justify-end', 'gap-6',
                    'bg-gray-50', 'dark:bg-gray-900/40',
                    'px-6', 'py-4',
                ])
            }}
        >
            {{ $footer }}
        </footer>
    @endisset
</section>
