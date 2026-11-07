<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Profil</h2>
    </x-slot>

    <div class="max-w-xl mx-auto space-y-6">

        <div class="p-6 bg-white shadow-sm rounded-md">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>

</x-app-layout>
