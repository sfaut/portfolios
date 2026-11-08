{{--
    Changement du mot de passe, après saisie du mot de passe actuel (PasswordController).

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation, dans le sac updatePassword.
--}}
<x-card>
    <x-slot:title>Modification du mot de passe</x-slot:title>
    <x-slot:description>Sécurisez votre compte avec un mot de passe long et difficile à retrouver.</x-slot:description>

    <form id="update-password-form" method="post" action="{{ route('password.update') }}" class="flex flex-col gap-6">
        @csrf
        @method('put')

        <div class="flex flex-col gap-1">
            <x-input-label for="update_password_current_password" value="Mot de passe actuel"/>
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="w-full" autocomplete="current-password"/>
            <x-input-error :messages="$errors->updatePassword->get('current_password')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="update_password_password" value="Nouveau mot de passe"/>
            <x-text-input id="update_password_password" name="password" type="password" class="w-full" autocomplete="new-password"/>
            <x-input-error :messages="$errors->updatePassword->get('password')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="update_password_password_confirmation" value="Confirmation du nouveau mot de passe"/>
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full" autocomplete="new-password"/>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')"/>
        </div>

    </form>

    <x-slot:footer>
        @if (session('status') === 'password-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm"
            >Sauvegardé</p>
        @endif

        <x-primary-button form="update-password-form">Sauvegarder</x-primary-button>
    </x-slot:footer>
</x-card>
