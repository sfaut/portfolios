<x-empty-layout>

    <div class="min-h-screen bg-gray-200 dark:bg-gray-900">

        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow-sm mb-6">
                <div class="max-w-7xl mx-auto py-4 px-6">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="pb-6">{{ $slot }}</main>

    </div>

</x-empty-layout>
