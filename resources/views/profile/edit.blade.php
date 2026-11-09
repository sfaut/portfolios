{{--
    Profil, première page : les informations de l'utilisateur (nom, prénom, adresse).

    @var \App\Models\User $user Utilisateur connecté, lu par les formulaires inclus.
--}}
<x-profile-layout title="Profil" current="profile.edit">
    @include('profile.partials.update-profile-information-form')
</x-profile-layout>
