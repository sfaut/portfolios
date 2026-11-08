<x-guest-layout>

    <div class="text-sm text-gray-400">
        Vous avez oublié votre mot de passe ? Pas de problèmes.
        Renseignez votre adresse e-mail et nous vous transmettrons par e-mail un lien
        de réinitialisation qui vous permettra d'en choisir un nouveau.
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')"/>

    <form method="post" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf

        <!-- Email Address -->
        <div class="flex flex-col gap-1">
            <x-input-label for="email" value="E-mail"/>
            <x-text-input id="email" class="w-full" type="email" name="email" :value="old('email')" required autofocus/>
            <x-input-error :messages="$errors->get('email')"/>
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>
                Réinitialiser mon mot de passe
            </x-primary-button>
        </div>
    </form>

</x-guest-layout>
