<x-guest-layout>

    <form method="post" action="{{ route('password.store') }}" class="flex flex-col gap-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="flex flex-col gap-1">
            <x-input-label for="email" value="E-mail"/>

            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                class="w-full"
                required autocomplete="username"
            />

            <x-input-error :messages="$errors->get('email')"/>
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1">
            <x-input-label for="password" value="Mot de passe"/>

            <x-text-input
                id="password"
                type="password"
                name="password"
                class="w-full"
                required
                autocomplete="new-password"
            />

            <x-input-error :messages="$errors->get('password')"/>
        </div>

        <!-- Confirm Password -->
        <div class="flex flex-col gap-1">
            <x-input-label for="password_confirmation" value="Confirmation du mot de passe"/>

            <x-text-input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="w-full"
                required autocomplete="new-password"
            />

            <x-input-error :messages="$errors->get('password_confirmation')"/>
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>
                Réinitialiser le mot de passe
            </x-primary-button>
        </div>
    </form>

</x-guest-layout>
