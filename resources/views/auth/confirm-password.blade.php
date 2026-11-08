{{--
    Confirmation du mot de passe, demandée par le middleware password.confirm (pages du profil).

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        Cette partie de l'application est protégée : confirmez votre mot de passe pour continuer.
    </div>

    <form method="post" action="{{ route('password.confirm') }}" class="flex flex-col gap-4">
        @csrf

        <div class="flex flex-col gap-1">
            <x-input-label for="password" value="Mot de passe"/>
            <x-text-input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('password')"/>
        </div>

        <div class="flex justify-end">
            <x-primary-button>Confirmer</x-primary-button>
        </div>
    </form>
</x-guest-layout>
