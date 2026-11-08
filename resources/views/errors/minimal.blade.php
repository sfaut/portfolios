{{-- Layout of the error pages (errors::<code> views): the guest layout, so they follow the chosen theme. --}}
<x-guest-layout>
    <div class="flex flex-col items-center gap-6">
        <div class="flex items-center gap-4 text-lg uppercase tracking-wider text-gray-500 dark:text-gray-400">
            <span class="border-r border-gray-400 dark:border-gray-600 pr-4">@yield('code')</span>
            <span>@yield('message')</span>
        </div>

        <x-link.primary :target="url('/')">Retour à l'accueil</x-link.primary>
    </div>
</x-guest-layout>
