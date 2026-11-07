<x-guest-layout>

    <x-auth-session-status class="mb-4" :status="session('status')"/>

    <form method="post" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail"/>
            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email')"
                autofocus
                autocomplete="username"
                class="block mt-1 w-full"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2"/>
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Mot de passe"/>
            <x-text-input
                type="password"
                id="password"
                name="password"
                class="block mt-1 w-full"
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2"/>
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input type="checkbox" id="remember_me" name="remember" class="rounded-sm dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-xs focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Se souvenir de moi</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            @endif

            <x-primary-button class="ms-3">Connecter</x-primary-button>
        </div>
    </form>
</x-guest-layout>
