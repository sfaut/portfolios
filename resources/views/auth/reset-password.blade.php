{{--
    Choix d'un nouveau mot de passe, depuis le lien de réinitialisation reçu par e-mail.

    @var \Illuminate\Http\Request $request Requête du lien : jeton (paramètre de route token) et adresse
        (paramètre email).
    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-guest-layout>

    <form method="post" action="{{ route('password.store') }}" class="flex flex-col gap-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="flex flex-col gap-1">
            <x-input-label for="email" value="E-mail"/>
            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                autocomplete="username"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('email')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="password" value="Mot de passe"/>
            <x-text-input
                id="password"
                type="password"
                name="password"
                autocomplete="new-password"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('password')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="password_confirmation" value="Confirmation du mot de passe"/>
            <x-text-input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('password_confirmation')"/>
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>Réinitialiser le mot de passe</x-primary-button>
        </div>
    </form>

</x-guest-layout>
