<x-empty-layout>

    <div @class([
        'min-h-screen',
        'flex', 'flex-col',
        'items-center', 'justify-center',
        'bg-gray-100',
    ])>

        <div>
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-indigo-800"/>
            </a>
        </div>

        <h1 class="mt-6 text-4xl tracking-widest drop-shadow-md font-medium text-indigo-800">Portfolios</h1>

        <div @class([
            'max-w-md', 'w-full',
            'mt-6', 'px-6', 'py-6',
            'bg-white',
            'shadow-md', 'overflow-hidden',
            'rounded-lg',
        ])>
            {{ $slot }}
        </div>

    </div>

</x-empty-layout>
