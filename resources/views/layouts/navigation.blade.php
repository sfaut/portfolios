<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex sm:gap-10">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('portfolio.index') }}">
                        <x-application-logo class="block h-8 w-auto fill-current text-indigo-800 dark:text-gray-200"/>
                    </a>
                </div>

                <div class="hidden gap-8 sm:-my-px sm:flex">
                    <x-nav-link :href="route('portfolio.index')" :active="request()->routeIs(['portfolio.*', 'account.*', 'operation.*'])">
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
                                <span class="text-xs font-normal text-gray-400">{{ auth()->user()->email }}</span><br>
                            </div>

                            <div>
                                <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profil
                        </x-dropdown-link>

                        {{-- Theme selector (resources/js/theme.js); a click keeps the menu open. --}}
                        @php
                            $themes = [
                                'light' => ['Clair', 'M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z'],
                                'dark' => ['Sombre', 'M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z'],
                                'system' => ['Système', 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25'],
                            ];
                        @endphp

                        <div class="flex flex-col gap-1.5 border-y border-gray-100 dark:border-gray-600 px-4 pt-2 pb-3" @click.stop>
                            <div id="theme-label" class="text-xs text-gray-500 dark:text-gray-400">Thème</div>

                            <div role="group" aria-labelledby="theme-label" class="grid grid-cols-3 gap-1 rounded-md bg-gray-100 dark:bg-gray-800 p-1">
                                @foreach ($themes as $mode => [$label, $icon])
                                    <button
                                        type="button"
                                        :aria-pressed="$store.theme.mode === '{{ $mode }}'"
                                        @click="$store.theme.set('{{ $mode }}')"
                                        @class([
                                            'flex', 'flex-col', 'items-center', 'gap-1',
                                            'rounded', 'px-1', 'py-1.5',
                                            'text-xs', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-200',
                                            'aria-pressed:bg-white', 'aria-pressed:text-gray-900', 'aria-pressed:shadow-sm',
                                            'dark:aria-pressed:bg-gray-600', 'dark:aria-pressed:text-white',
                                            'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500',
                                            'transition', 'ease-in-out', 'duration-150',
                                        ])
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                                        </svg>
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
