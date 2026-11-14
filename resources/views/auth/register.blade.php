{{--
    Inscription d'un nouvel utilisateur, qui reçoit ensuite un lien de vérification de son adresse ; en
    pied, le lien vers la connexion.

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>
    <x-slot:title>Créer un compte</x-slot:title>
    <x-slot:description>Vous recevrez ensuite un lien pour vérifier votre adresse.</x-slot:description>

    <form method="post" action="{{ route('register') }}" class="flex flex-col gap-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-1">
                <x-input-label for="last_name" value="Nom"/>
                <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" autocomplete="family-name" class="w-full"/>
                <x-input-error :messages="$errors->get('last_name')"/>
            </div>

            <div class="flex flex-col gap-1">
                <x-input-label for="first_name" value="Prénom"/>
                <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" autocomplete="given-name" class="w-full"/>
                <x-input-error :messages="$errors->get('first_name')"/>
            </div>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="email" value="E-mail"/>
            <x-text-input id="email" type="email" name="email" :value="old('email')" autocomplete="username" class="w-full"/>
            <x-input-error :messages="$errors->get('email')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="password" value="Mot de passe"/>
            <x-text-input id="password" type="password" name="password" autocomplete="new-password" class="w-full"/>
            <x-input-error :messages="$errors->get('password')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="password_confirmation" value="Confirmation du mot de passe"/>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="w-full"/>
            <x-input-error :messages="$errors->get('password_confirmation')"/>
        </div>

        <x-button variant="primary" type="submit" class="w-full">Créer un compte</x-button>
    </form>

    <x-slot:footer>
        Déjà inscrit&nbsp;?
        <a
            href="{{ route('login') }}"
            class="font-medium text-indigo-700 dark:text-indigo-400 underline hover:no-underline rounded-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
        >Se connecter</a>
    </x-slot:footer>
</x-guest-layout>
