{{--
    Barre de navigation : logo, lien des portfolios et menu de l'utilisateur (profil, thème, déconnexion).

    Aucune variable externe : l'utilisateur vient de auth(), le lien actif de request().
--}}
<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex sm:gap-10">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('portfolio.index') }}">
                        <x-application-logo class="block h-8 w-auto text-indigo-800 dark:text-gray-200"/>
                    </a>
                </div>

                <div class="hidden gap-8 sm:-my-px sm:flex">
                    <x-nav-link :href="route('portfolio.index')" :active="request()->routeIs(['portfolio.*', 'account.*', 'operation.*', 'analytic-axis.*'])">
                        Portfolios
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="flex items-center">
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
                        <x-dropdown-link :href="route('profile.edit')">
                            Profil
                        </x-dropdown-link>

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
