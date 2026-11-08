{{--
    Demande d'un lien de réinitialisation du mot de passe. Le message de retour est le même que
    l'adresse ait un compte ou non (PasswordResetLinkController).

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>

    <div class="text-sm text-gray-500 dark:text-gray-400">
        Vous avez oublié votre mot de passe ? Pas de problème.
        Renseignez votre adresse e-mail et nous vous transmettrons par e-mail un lien
        de réinitialisation qui vous permettra d'en choisir un nouveau.
    </div>

    <x-auth-session-status :status="session('status')"/>

    <form method="post" action="{{ route('password.email') }}" class="flex flex-col gap-4">
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

        <div class="flex items-center justify-end">
            <x-primary-button>Réinitialiser mon mot de passe</x-primary-button>
        </div>
    </form>

</x-guest-layout>
