<x-card>
    <x-slot:title>Informations du profil</x-slot:title>
    <x-slot:description>Mettez à jour les informations personnelles de votre compte.</x-slot:description>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="hidden">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-6">
        @method('patch')
        @csrf

        <div class="flex flex-col gap-1">
            <x-input-label for="last_name" value="Nom"/>
            <x-text-input
                id="last_name"
                type="text"
                name="last_name"
                :value="old('last_name', $user->last_name)"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('last_name')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="first_name" value="Prénom"/>
            <x-text-input
                id="first_name"
                type="text"
                name="first_name"
                :value="old('first_name', $user->first_name)"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('first_name')"/>
        </div>

        <div class="flex flex-col gap-1">
            <x-input-label for="email" value="E-mail"/>
            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email', $user->email)"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('email')"/>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="text-sm text-gray-800 dark:text-gray-200">
                    {{ __('Your email address is unverified.') }}

                    <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="font-medium text-sm text-green-600 dark:text-green-400">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            @endif
        </div>

        <div class="flex items-center gap-6">

            <x-primary-button>Sauvegarder</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm"
                >Sauvegardé</p>
            @endif
        </div>
    </form>
</x-card>
