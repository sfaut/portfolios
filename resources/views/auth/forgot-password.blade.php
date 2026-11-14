{{--
    Demande d'un lien de réinitialisation du mot de passe. Le message de retour est le même que
    l'adresse ait un compte ou non (PasswordResetLinkController). En pied, le retour à la connexion.

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>
    <x-slot:title>Mot de passe oublié</x-slot:title>
    <x-slot:description>Indiquez votre adresse e-mail&nbsp;: vous recevrez un lien pour choisir un nouveau mot de passe.</x-slot:description>

    <x-auth-session-status :status="session('status')"/>

    <form method="post" action="{{ route('password.email') }}" class="flex flex-col gap-5">
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

        <x-primary-button class="w-full justify-center">Envoyer le lien</x-primary-button>
    </form>

    <x-slot:footer>
        <a
            href="{{ route('login') }}"
            class="font-medium text-indigo-700 dark:text-indigo-400 underline hover:no-underline rounded-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
        >Retour à la connexion</a>
    </x-slot:footer>
</x-guest-layout>
