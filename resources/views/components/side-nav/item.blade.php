@props([
    'href',
    'current' => false,
    'status' => null,
    'count' => null,
])

{{--
    Lien d'une navigation latérale (<x-side-nav>), précédé d'une pastille d'état et suivi d'un nombre,
    tous deux facultatifs. La pastille porte aussi son état en texte, pour les lecteurs d'écran. Le lien
    courant porte aria-current="true", posé par le serveur ($current) ou au défilement par sectionSpy.

    @var string $href Adresse du lien, une ancre de la page (« #php »).
    @var bool $current Si le lien est le lien courant au chargement.
    @var string|null $status État de la cible : ok, warning ou danger ; null sans pastille.
    @var int|null $count Nombre affiché à droite (alertes…), null ou 0 pour aucun.
    @var \Illuminate\View\ComponentSlot $slot Texte du lien.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du lien.
--}}
<li class="flex shrink-0">
    <a
        href="{{ $href }}"
        @if ($current) aria-current="true" @endif
        {{
            $attributes->class([
                'relative', 'flex', 'grow', 'items-center', 'gap-2.5', 'px-3', 'py-2', 'text-sm', 'whitespace-nowrap',
                '-mb-px', 'border-b-2', 'lg:mb-0', 'lg:-ml-px', 'lg:border-b-0', 'lg:border-l-2', 'border-transparent',
                'text-gray-600', 'dark:text-gray-400', 'hover:text-gray-900', 'dark:hover:text-white',
                'hover:border-gray-300', 'dark:hover:border-gray-500',
                'aria-[current=true]:border-indigo-500', 'dark:aria-[current=true]:border-indigo-400',
                'aria-[current=true]:font-semibold',
                'aria-[current=true]:text-gray-900', 'dark:aria-[current=true]:text-white',
                'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500',
            ])
        }}
    >
        @if ($status !== null)
            <span
                @class([
                    'size-2', 'shrink-0', 'rounded-full',
                    'bg-emerald-500' => $status === 'ok',
                    'bg-amber-500' => $status === 'warning',
                    'bg-red-600 dark:bg-red-500' => $status === 'danger',
                ])
                aria-hidden="true"
            ></span>
        @endif

        <span class="grow">{{ $slot }}</span>

        @if ($status === 'warning')
            <span class="sr-only">(à surveiller)</span>
        @elseif ($status === 'danger')
            <span class="sr-only">(anomalie)</span>
        @endif

        @if ($count)
            <span
                @class([
                    'rounded-full', 'px-2', 'text-xs', 'font-semibold', 'tabular-nums',
                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $status === null || $status === 'ok',
                    'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' => $status === 'warning',
                    'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' => $status === 'danger',
                ])
            >{{ $count }}</span>
        @endif
    </a>
</li>
