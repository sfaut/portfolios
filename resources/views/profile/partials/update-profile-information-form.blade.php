{{--
    Informations du profil : nom, prénom et adresse, avec l'état de vérification de l'adresse.

    @var \App\Models\User $user Utilisateur connecté.
    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-card>
    <x-slot:title>Informations du profil</x-slot:title>
    <x-slot:description>Mettez à jour les informations personnelles de votre compte.</x-slot:description>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="hidden">
        @csrf
    </form>

    <form id="update-profile-information-form" method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-6">
        @method('patch')
        @csrf

        <div class="flex flex-col gap-1">
            <x-input-label for="last_name" value="Nom"/>
            <x-text-input
                id="last_name"
                type="text"
                name="last_name"
                :value="old('last_name', $user->last_name)"
                autocomplete="family-name"
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
                autocomplete="given-name"
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
                autocomplete="username"
                class="w-full"
            />
            <x-input-error :messages="$errors->get('email')"/>

            @if (! $user->hasVerifiedEmail())
                <p class="text-sm text-gray-800 dark:text-gray-200">
                    Votre adresse e-mail n'est pas vérifiée.

                    <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                        Renvoyer le lien de vérification
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="font-medium text-sm text-success-600 dark:text-success-400">
                        Un nouveau lien de vérification vient d'être envoyé à votre adresse e-mail.
                    </p>
                @endif
            @endif
        </div>

    </form>

    <x-slot:footer>
        @if (session('status') === 'profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm"
            >Sauvegardé</p>
        @endif

        <x-primary-button form="update-profile-information-form">Sauvegarder</x-primary-button>
    </x-slot:footer>
</x-card>
