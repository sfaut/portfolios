{{--
    Layout of the error pages: the guest layout, so they follow the chosen theme. The message is
    the title of the page, the code its description.

    The pages of each status code (401, 403, 404, 419, 500, 503…) come from Laravel
    (vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/views): they only set
    the code and the message, and extend errors::minimal, which Laravel looks up in this
    folder first, so they use this layout. Only the pages that Laravel lacks (501) or
    that the project changes live here; publishing a copy of Laravel's pages would freeze
    them at its current version.
--}}
<x-guest-layout>
    <x-slot:title>@yield('message')</x-slot:title>
    <x-slot:description>Erreur @yield('code')</x-slot:description>

    <x-button variant="primary" :href="url('/')" class="self-start">Retour à l'accueil</x-button>
</x-guest-layout>
