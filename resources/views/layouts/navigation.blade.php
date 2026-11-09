{{--
    Barre de navigation : le logo, qui mène à la liste des portfolios, puis, dans un portfolio, le
    sélecteur de portfolio et la navigation entre ses sections (<x-portfolio-nav>), toujours à la même
    place, et le menu de l'utilisateur (profil, thème, déconnexion). Sur un écran étroit, sélecteur et
    navigation passent sur une seconde ligne, en pleine largeur.

    Variables passées par le composeur de AppServiceProvider (App\Support\PortfolioContext) :

    @var \App\Models\Portfolio|null $current_portfolio Portfolio de la page, null hors d'un portfolio.
    @var string $current_section Section courante : dashboard, accounts, analytic-axes, ou '' sans section.
    @var \Illuminate\Support\Collection<int, \App\Models\Portfolio> $portfolios Portfolios de l'utilisateur, par nom, vide hors d'un portfolio.
--}}
<nav aria-label="Navigation principale" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 py-2 sm:h-14 sm:py-0">
            <a href="{{ route('portfolio.index') }}" class="order-1 shrink-0 rounded-md focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500">
                <x-application-logo class="block h-8 w-auto text-indigo-800 dark:text-gray-200"/>
            </a>

            @if ($current_portfolio !== null)
                <div class="order-3 sm:order-2 flex w-full sm:w-auto flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                    <div class="w-full sm:w-52">
                        <x-dropdown align="left" width="w-64">
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    @class([
                                        'flex', 'w-full', 'items-center', 'justify-between', 'gap-2',
                                        'rounded-md', 'border', 'border-gray-300', 'dark:border-gray-600',
                                        'px-3', 'py-1.5',
                                        'text-sm', 'font-medium', 'text-gray-800', 'dark:text-gray-100',
                                        'hover:bg-gray-50', 'dark:hover:bg-gray-700',
                                        'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500',
                                    ])
                                >
                                    <span class="truncate"><span class="sr-only">Portfolio : </span>{{ $current_portfolio->name }}</span>
                                    <x-lucide-chevrons-up-down class="size-4 shrink-0 text-gray-400" aria-hidden="true"/>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @foreach ($portfolios as $portfolio)
                                    <x-dropdown-link :href="route('portfolio.dashboard', $portfolio)" class="flex items-center justify-between gap-2">
                                        <span class="truncate">{{ $portfolio->name }}</span>
                                        @if ($portfolio->is($current_portfolio))
                                            <span class="sr-only">(portfolio affiché)</span>
                                            <x-lucide-check class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400" aria-hidden="true"/>
                                        @endif
                                    </x-dropdown-link>
                                @endforeach

                                <div class="border-t border-gray-100 dark:border-gray-600"></div>

                                <x-dropdown-link :href="route('portfolio.edit', $current_portfolio)">Modifier ce portfolio</x-dropdown-link>
                                <x-dropdown-link :href="route('portfolio.index')">Tous les portfolios</x-dropdown-link>
                                <x-dropdown-link :href="route('portfolio.create')">Créer un portfolio</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <x-portfolio-nav :portfolio="$current_portfolio" :current="$current_section"/>
                </div>
            @endif

            <div class="order-2 sm:order-3 ml-auto flex items-center">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button
                            @class([
                                'inline-flex', 'items-center', 'gap-2',
                                'rounded-md', 'bg-white', 'dark:bg-gray-800',
                                'px-3', 'py-2',
                                'text-sm', 'leading-4', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-300',
                                'transition', 'ease-in-out', 'duration-150',
                            ])
                        >
                            <div class="text-end">
                                <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span><br>
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</span><br>
                            </div>

                            <div>
                                <x-lucide-chevron-down class="size-4" aria-hidden="true"/>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')" icon="circle-user-round">
                            Profil
                        </x-dropdown-link>

                        @if (auth()->user()->is_admin)
                            <x-dropdown-link :href="route('admin.server')" icon="server">
                                État du serveur
                            </x-dropdown-link>
                        @endif

                        {{-- Theme selector (resources/js/theme.js); a click keeps the menu open. --}}

                        <div class="flex flex-col gap-1.5 border-y border-gray-100 dark:border-gray-600 px-4 pt-2 pb-3" @click.stop>
                            <div id="theme-label" class="text-xs text-gray-500 dark:text-gray-400">Thème</div>

                            <div role="group" aria-labelledby="theme-label" class="grid grid-cols-3 gap-1 rounded-md bg-gray-100 dark:bg-gray-800 p-1">
                                @foreach (['light' => ['Clair', 'sun'], 'dark' => ['Sombre', 'moon'], 'system' => ['Système', 'monitor']] as $mode => [$label, $icon])
                                    <button
                                        type="button"
                                        :aria-pressed="$store.theme.mode === '{{ $mode }}'"
                                        @click="$store.theme.set('{{ $mode }}')"
                                        @class([
                                            'flex', 'flex-col', 'items-center', 'gap-1',
                                            'rounded-sm', 'px-1', 'py-1.5',
                                            'text-xs', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-200',
                                            'aria-pressed:bg-white', 'aria-pressed:text-gray-900', 'aria-pressed:shadow-sm',
                                            'dark:aria-pressed:bg-gray-600', 'dark:aria-pressed:text-white',
                                            'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500',
                                            'transition', 'ease-in-out', 'duration-150',
                                        ])
                                    >
                                        <x-dynamic-component :component="'lucide-'.$icon" class="size-4" aria-hidden="true"/>
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <form method="post" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                icon="log-out"
                                onclick="event.preventDefault(); this.closest('form').submit()"
                            >
                                Déconnexion
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
