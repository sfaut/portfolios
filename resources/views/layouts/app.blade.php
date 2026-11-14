{{--
    Mise en page des pages de l'application (App\View\Components\AppLayout).

    @var \Closure(): string $container Classes du conteneur partagé par le bandeau et le contenu (AppLayout::container()).
    @var \Illuminate\View\ComponentSlot|null $header Bandeau de la page.
    @var \Illuminate\View\ComponentSlot $slot Contenu.
--}}
<x-empty-layout>

    <div class="min-h-screen bg-gray-200 dark:bg-gray-900">

        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow-sm">
                <div @class(['py-4', $container()])>
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="py-6">
            <div class="{{ $container() }}">{{ $slot }}</div>
        </main>

    </div>

    <x-toast-region/>

</x-empty-layout>
