
<section>

    <header>
        <h2 class="text-lg font-semibold">Informations du profil</h2>
        <p class="mt-1 text-sm text-gray-400">
            Mettez à jour les informations personnelles de votre compte.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @method('patch')
        @csrf

        <div>
            <x-input-label for="last_name" value="Nom"/>
            <x-text-input
                id="last_name"
                type="text"
                name="last_name"
                :value="old('last_name', $user->last_name)"
                class="mt-1 block w-full"
            />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')"/>
        </div>

        <div>
            <x-input-label for="first_name" value="Prénom"/>
            <x-text-input
                id="first_name"
                type="text"
                name="first_name"
                :value="old('first_name', $user->first_name)"
                class="mt-1 block w-full"
            />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')"/>
        </div>

        <div>
            <x-input-label for="email" value="E-mail"/>
            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email', $user->email)"
                class="mt-1 block w-full"
            />
            <x-input-error class="mt-2" :messages="$errors->get('email')"/>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">

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
</section>
