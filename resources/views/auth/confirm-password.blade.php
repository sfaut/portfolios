{{--
    Confirmation du mot de passe, demandée par le middleware password.confirm (pages du profil).

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>
    <x-slot:title>Confirmer le mot de passe</x-slot:title>
    <x-slot:description>Cette partie de l'application est protégée&nbsp;: confirmez votre mot de passe pour continuer.</x-slot:description>

    <form method="post" action="{{ route('password.confirm') }}" class="flex flex-col gap-5">
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

        <x-button variant="primary" type="submit" class="w-full">Confirmer</x-button>
    </form>
</x-guest-layout>
