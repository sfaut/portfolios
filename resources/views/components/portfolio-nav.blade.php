@props([
    'portfolio',
    'current',
])

{{--
    Navigation entre les pages d'un portfolio : tableau de bord, comptes et axes analytiques, la page
    courante marquée (aria-current), en contrôle segmenté.

    @var \App\Models\Portfolio $portfolio Portfolio des pages.
    @var string $current Page courante : dashboard, accounts ou analytic-axes.
--}}
<nav aria-label="Pages du portfolio" {{ $attributes->class(['flex', 'gap-1', 'rounded-md', 'bg-gray-200', 'dark:bg-gray-700', 'p-1']) }}>
    @foreach ([
        'dashboard' => ['Tableau de bord', route('portfolio.dashboard', $portfolio)],
        'accounts' => ['Comptes', route('portfolio.show', $portfolio)],
        'analytic-axes' => ['Axes analytiques', route('analytic-axis.index', $portfolio)],
    ] as $page => [$text, $url])
        <a
            href="{{ $url }}"
            @if ($page === $current) aria-current="page" @endif
            @class([
                'rounded', 'px-3', 'py-1.5', 'text-sm', 'font-medium', 'whitespace-nowrap',
                'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500',
                'bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 shadow-sm' => $page === $current,
                'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' => $page !== $current,
            ])
        >{{ $text }}</a>
    @endforeach
</nav>
