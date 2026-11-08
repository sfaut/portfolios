<x-empty-layout>

    <div @class([
        'min-h-screen',
        'flex', 'flex-col', 'gap-6',
        'items-center', 'justify-center',
        'bg-gray-100', 'dark:bg-gray-900',
    ])>

        <div>
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-indigo-800 dark:text-indigo-400"/>
            </a>
        </div>

        <h1 class="text-4xl tracking-widest drop-shadow-md font-medium text-indigo-800 dark:text-indigo-400">Portfolios</h1>

        <div @class([
            'max-w-md', 'w-full',
            'flex', 'flex-col', 'gap-4',
            'px-6', 'py-6',
            'bg-white', 'dark:bg-gray-800',
            'shadow-md', 'overflow-hidden',
            'rounded-lg',
        ])>
            {{ $slot }}
        </div>

    </div>

</x-empty-layout>
