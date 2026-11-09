{{--
    Données et compte : téléchargement des données de l'utilisateur, puis suppression de son compte.

    @var \App\Models\User $user Utilisateur connecté, lu par les formulaires inclus.
--}}
<x-profile-layout title="Données et compte" current="profile.account">
    @include('profile.partials.export-data-form')

    @include('profile.partials.delete-user-form')
</x-profile-layout>
