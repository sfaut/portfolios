<x-guest-layout>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        Cette partie de l'application est protégée : confirmez votre mot de passe pour continuer.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-4">
        @csrf

        <!-- Password -->
        <div class="flex flex-col gap-1">
            <x-input-label for="password" value="Mot de passe" />

            <x-text-input id="password" class="w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex justify-end">
            <x-primary-button>
                Confirmer
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
