{{--
    Pagination compacte : première, précédente, page courante, suivante et dernière page.
    S'appelle depuis un paginateur, $records->links('pagination.narrow') ;
    https://laravel.com/docs/master/pagination

    @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator Paginateur, fourni par links().
--}}
@if ($paginator->hasPages())

    <nav @class([
        'grid', 'grid-flow-col', 'auto-cols-min',
        'shadow-sm',
        'divide-x', 'divide-gray-200', 'dark:divide-gray-700',
        'text-xs', 'text-gray-600', 'dark:text-gray-300',
    ])>

        {{-- First page --}}
        <a
            href="{{ $paginator->url(1) }}"
            aria-label="Première page"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'p-2', 'bg-white', 'dark:bg-gray-800', 'rounded-l-md',
            ])
        >
            <x-lucide-chevrons-left class="size-4" aria-hidden="true"/>
        </a>

        {{-- Previous page --}}
        <a
            href="{{ $paginator->previousPageUrl() }}"
            aria-label="Page précédente"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'p-2', 'bg-white', 'dark:bg-gray-800',
            ])
        >
            <x-lucide-chevron-left class="size-4" aria-hidden="true"/>
        </a>

        <div class="inline-flex items-center justify-center bg-white dark:bg-gray-800 p-2 tracking-widest text-nowrap">
            Page {{ $paginator->currentPage() }}
            / {{ $paginator->lastPage() }}
        </div>

        {{-- Next page --}}
        <a
            href="{{ $paginator->nextPageUrl() }}"
            aria-label="Page suivante"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'p-2', 'bg-white', 'dark:bg-gray-800',
            ])
        >
            <x-lucide-chevron-right class="size-4" aria-hidden="true"/>
        </a>

        <!-- Right double chevron -->
        <a
            href="{{ $paginator->url($paginator->lastPage()) }}"
            aria-label="Dernière page"
            @class([
                'inline-flex', 'items-center', 'justify-center',
                'rounded-r-md', 'p-2', 'bg-white', 'dark:bg-gray-800',
            ])
        >
            <x-lucide-chevrons-right class="size-4" aria-hidden="true"/>
        </a>

    </nav>

@endif
