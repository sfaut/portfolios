<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('portfolio.index') }}">
                        <x-application-logo class="block h-8 w-auto fill-current text-indigo-800 dark:text-gray-200"/>
                    </a>
                </div>

                <div class="hidden gap-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('portfolio.index')" :active="request()->routeIs(['portfolio.*', 'account.*', 'operation.*'])">
                        Portfolios
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="flex items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            @class([
                                'inline-flex', 'items-center',
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

                            <div class="ms-2">
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
