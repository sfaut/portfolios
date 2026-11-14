{{--
    Mise en page des pages de l'application (App\View\Components\AppLayout).

    @var bool $wide Si la page est large : la barre et le bandeau prennent sa largeur.
    @var \Illuminate\View\ComponentSlot|null $header Bandeau de la page.
    @var \Illuminate\View\ComponentSlot $slot Contenu.
--}}
<x-empty-layout>

    <div class="min-h-screen bg-gray-200 dark:bg-gray-900">

        @include('layouts.navigation', ['wide' => $wide])

        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow-sm">
                <div @class([
                    'py-4',
                    'max-w-7xl mx-auto px-6' => ! $wide,
                    \App\View\Components\AppLayout::WIDE_CONTAINER => $wide,
                ])>
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="py-6">{{ $slot }}</main>

    </div>

    <x-toast-region/>

</x-empty-layout>
