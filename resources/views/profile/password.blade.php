{{--
    Mot de passe du profil : son changement, après saisie du mot de passe actuel.

    @var \App\Models\User $user Utilisateur connecté.
--}}
<x-profile-layout title="Mot de passe" description="Le mot de passe qui protège votre compte." current="profile.password">
    @include('profile.partials.update-password-form')
</x-profile-layout>
