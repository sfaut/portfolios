<x-guest-layout>

    <form method="post" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf

        <div class="flex flex-col gap-1">
            <x-input-label for="last_name" value="Nom"/>
            <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" class="w-full"/>
            <x-input-error :messages="$errors->get('last_name')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="first_name" value="Prénom"/>
            <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" class="w-full"/>
            <x-input-error :messages="$errors->get('first_name')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="email" value="E-mail"/>
            <x-text-input id="email" type="email" name="email" :value="old('email')" class="w-full"/>
            <x-input-error :messages="$errors->get('email')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="password" value="Mot de passe"/>
            <x-text-input id="password" type="password" name="password" class="w-full"/>
            <x-input-error :messages="$errors->get('password')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="password_confirmation" value="Confirmation du mot de passe"/>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="w-full"/>
            <x-input-error :messages="$errors->get('password_confirmation')"/>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a
                href="{{ route('login') }}"
                class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
            >
                Déjà enregistré ?
            </a>

            <x-primary-button>
                S'enregistrer
            </x-primary-button>
        </div>
    </form>

</x-guest-layout>
