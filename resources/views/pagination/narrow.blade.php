{{--
    Must be call only from paginator instance
    $records->links('pagination.narrow')
    https://laravel.com/docs/master/pagination
--}}
@if ($paginator->hasPages())

    <nav @class([
        'grid', 'grid-flow-col', 'auto-cols-min',
        'shadow-sm',
        'divide-x',
        'text-xs', 'text-gray-600', 'dark:text-gray-300',
    ])>

        {{-- First page --}}
        <a
            href="{{ $paginator->url(1) }}"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'p-2', 'bg-white', 'dark:bg-gray-800', 'rounded-l-md',
            ])
        >
            <svg class="size-4" viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
                stroke-linecap="round"
            >
                <path d="M 16,5 L 11,10 L 16,15 M 9,5 L 4,10 L 9,15"/>
            </svg>
        </a>

        {{-- Previous page --}}
        <a
            href="{{ $paginator->previousPageUrl() }}"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'p-2', 'bg-white', 'dark:bg-gray-800',
            ])
        >
            <svg class="size-4" viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
                stroke-linecap="round"
            >
                <path d="M 12,5 L 7,10 L 12,15"/>
            </svg>
        </a>

        <div class="inline-flex items-center justify-center bg-white dark:bg-gray-800 p-2 tracking-widest text-nowrap">
            Page {{ $paginator->currentPage() }}
            / {{ $paginator->lastPage() }}
        </div>

        {{-- Next page --}}
        <a
            href="{{ $paginator->nextPageUrl() }}"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'p-2', 'bg-white', 'dark:bg-gray-800',
            ])
        >
            <svg class="size-4" viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
                stroke-linecap="round"
            >
                <path d="M 8,5 L 13,10 L 8,15 "/>
            </svg>
        </a>

        <!-- Right double chevron -->
        <a
            href="{{ $paginator->url($paginator->lastPage()) }}"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'rounded-r-md', 'p-2', 'bg-white', 'dark:bg-gray-800',
            ])
        >
            <svg class="size-4" viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
                stroke-linecap="round"
            >
                <path d="M 4,5 L 9,10 L 4,15 M 11,5 L 16,10 L 11,15"/>
            </svg>
        </a>

    </nav>

@endif
