{{--
    Connexion : adresse électronique, mot de passe (affichable), « Se souvenir de moi » et lien vers le
    mot de passe oublié, puis, en pied, le lien vers l'inscription.

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>
    <x-slot:title>Connexion</x-slot:title>
    <x-slot:description>Connectez-vous pour retrouver vos portfolios.</x-slot:description>

    <x-auth-session-status :status="session('status')"/>

    <form method="post" action="{{ route('login') }}" class="flex flex-col gap-5">
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

        <div class="flex flex-wrap items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input type="checkbox" id="remember_me" name="remember" class="rounded-sm dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-xs focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-900">
                <span class="text-sm text-gray-600 dark:text-gray-400">Se souvenir de moi</span>
            </label>

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="text-sm font-medium text-indigo-700 dark:text-indigo-400 underline hover:no-underline rounded-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                >Mot de passe oublié&nbsp;?</a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">Se connecter</x-primary-button>
    </form>

    <x-slot:footer>
        Pas encore de compte&nbsp;?
        <a
            href="{{ route('register') }}"
            class="font-medium text-indigo-700 dark:text-indigo-400 underline hover:no-underline rounded-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
        >Créer un compte</a>
    </x-slot:footer>
</x-guest-layout>
