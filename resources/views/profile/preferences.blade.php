{{--
    Préférences du profil : le thème de l'interface.

    Avec JavaScript, l'interrupteur applique et enregistre le thème dès qu'il change (store theme),
    comme celui de la barre ; sans, le formulaire l'envoie par son bouton.

    @var \App\Models\User $user Utilisateur connecté.
    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-profile-layout title="Préférences" current="profile.preferences">
    <x-card>
        <x-slot:title>Apparence</x-slot:title>
        <x-slot:description>Le thème de l'interface, sur tous vos appareils.</x-slot:description>

        <form id="color-scheme-form" method="post" action="{{ route('profile.color-scheme') }}" class="flex flex-col gap-1">
            @csrf
            @method('put')

            <x-switch
                label="Thème"
                name="color_scheme"
                variant="color-scheme"
                labels
                :value="$user->color_scheme->value"
                model="$store.theme.mode"
                x-on:change="$store.theme.set($event.target.value)"
                class="self-start"
            />

            <x-input-error :messages="$errors->get('color_scheme')"/>

            @if (session('status') === 'color-scheme-updated')
                <p class="text-sm text-gray-600 dark:text-gray-400">Thème enregistré.</p>
            @endif

            <noscript>
                <x-primary-button icon="check">Enregistrer</x-primary-button>
            </noscript>
        </form>
    </x-card>
</x-profile-layout>
