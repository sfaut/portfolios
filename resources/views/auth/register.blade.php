<x-guest-layout>

    <form method="post" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="last_name" value="Nom"/>
            <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" class="block mt-1 w-full"/>
            <x-input-error :messages="$errors->get('last_name')" class="mt-2"/>
        </div>

        <div class="mt-4">
            <x-input-label for="first_name" value="Prénom"/>
            <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" class="block mt-1 w-full"/>
            <x-input-error :messages="$errors->get('first_name')" class="mt-2"/>
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="E-mail"/>
            <x-text-input id="email" type="email" name="email" :value="old('email')" class="block mt-1 w-full"/>
            <x-input-error :messages="$errors->get('email')" class="mt-2"/>
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Mot de passe"/>
            <x-text-input id="password" type="password" name="password" class="block mt-1 w-full"/>
            <x-input-error :messages="$errors->get('password')" class="mt-2"/>
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmation du mot de passe"/>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="block mt-1 w-full"/>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2"/>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a
                href="{{ route('login') }}"
                class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Déjà enregistré ?
            </a>

            <x-primary-button class="ms-4">
                S'enregistrer
            </x-primary-button>
        </div>
    </form>

</x-guest-layout>
