{{--
    Profil, première page : la photo, puis les informations de l'utilisateur (nom, prénom, adresse).

    @var \App\Models\User $user Utilisateur connecté, lu par les formulaires inclus.
    @var string $avatar_accept Types MIME acceptés pour la photo.
    @var string $avatar_formats Formats et poids acceptés pour la photo.
--}}
<x-profile-layout title="Profil" current="profile.edit">
    @include('profile.partials.update-avatar-form')

    @include('profile.partials.update-profile-information-form')
</x-profile-layout>
