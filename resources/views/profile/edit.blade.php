<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Profil</h2>
    </x-slot>

    <div class="max-w-xl mx-auto flex flex-col gap-6">

        @include('profile.partials.update-profile-information-form')

        @include('profile.partials.update-password-form')

        @include('profile.partials.delete-user-form')

    </div>

</x-app-layout>
