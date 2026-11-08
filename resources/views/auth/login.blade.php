{{--
    Connexion : adresse électronique, mot de passe (affichable), « Se souvenir de moi ».

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>

    <x-auth-session-status :status="session('status')"/>

    <form method="post" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf

        <div class="flex flex-col gap-1">
            <x-input-label for="email" value="E-mail"/>
            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email')"
                autocomplete="username"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('email')"/>
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1" x-data="{ passwordVisible: false }">
            <x-input-label for="password" value="Mot de passe"/>
            <div class="relative">
                <x-text-input
                    type="password"
                    x-bind:type="passwordVisible ? 'text' : 'password'"
                    id="password"
                    name="password"
                    class="w-full pe-10"
                    autocomplete="current-password"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 inset-e-0 flex items-center px-3 rounded-e-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                    aria-label="Afficher le mot de passe"
                    aria-controls="password"
                    aria-pressed="false"
                    x-bind:aria-pressed="passwordVisible.toString()"
                    x-on:click="passwordVisible = ! passwordVisible"
                >
                    <x-lucide-eye x-show="! passwordVisible" class="size-5" aria-hidden="true"/>
                    <x-lucide-eye-off x-show="passwordVisible" style="display: none" class="size-5" aria-hidden="true"/>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')"/>
        </div>

        <!-- Remember Me -->
        <div>
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input type="checkbox" id="remember_me" name="remember" class="rounded-sm dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-xs focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                <span class="text-sm text-gray-600 dark:text-gray-400">Se souvenir de moi</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            @endif

            <x-primary-button>Se connecter</x-primary-button>
        </div>
    </form>

    <p class="border-t border-gray-200 dark:border-gray-700 pt-4 text-center text-sm text-gray-600 dark:text-gray-400">
        Pas encore de compte ?
        <a
            href="{{ route('register') }}"
            class="font-medium text-indigo-700 dark:text-indigo-400 underline hover:no-underline rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
        >Créer un compte</a>
    </p>
</x-guest-layout>
