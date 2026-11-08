{{--
    Layout of the error pages: the guest layout, so they follow the chosen theme.

    The pages of each status code (401, 403, 404, 419, 500, 503…) come from Laravel
    (vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/views): they only set
    the code and the message, and extend errors::minimal, which Laravel looks up in this
    folder first, so they use this layout. Only the pages that Laravel lacks (501) or
    that the project changes live here; publishing a copy of Laravel's pages would freeze
    them at its current version.
--}}
<x-guest-layout>
    <div class="flex flex-col items-center gap-6">
        <div class="flex items-center gap-4 text-lg uppercase tracking-wider text-gray-500 dark:text-gray-400">
            <span class="border-r border-gray-400 dark:border-gray-600 pr-4">@yield('code')</span>
            <span>@yield('message')</span>
        </div>

        <x-link.primary :target="url('/')">Retour à l'accueil</x-link.primary>
    </div>
</x-guest-layout>
