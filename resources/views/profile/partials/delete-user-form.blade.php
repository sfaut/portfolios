{{--
    Suppression du compte et de toutes ses données, confirmée dans une boîte par le mot de passe.

    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation, dans le sac userDeletion.
--}}
<x-card danger>
    <x-slot:title>Suppression de compte</x-slot:title>
    <x-slot:description>
        Une fois votre compte supprimé, toutes ses ressources et données seront définitivement perdues.
        Avant de supprimer votre compte, veuillez télécharger les données
        ou informations que vous souhaitez conserver.
    </x-slot:description>

    <x-slot:footer>
        <x-danger-button type="button" commandfor="delete-user" command="show-modal">Supprimer</x-danger-button>
    </x-slot:footer>
</x-card>

<x-dialog id="delete-user" danger :show="$errors->userDeletion->isNotEmpty()">
    <x-slot:title>Supprimer votre compte ?</x-slot:title>
    <x-slot:description>
        Toutes ses ressources et données seront définitivement perdues.
        Saisissez votre mot de passe pour confirmer la suppression.
    </x-slot:description>

    <form id="user-deletion-form" method="post" action="{{ route('profile.destroy') }}" class="flex flex-col gap-1">
        @method('delete')
        @csrf

        <x-input-label for="password" value="Mot de passe" class="sr-only"/>

        <x-text-input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            class="w-3/4"
            placeholder="Mot de passe"
        />

        <x-input-error :messages="$errors->userDeletion->get('password')"/>
    </form>

    <x-slot:footer>
        <button type="button" commandfor="delete-user" command="close" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</button>
        <x-danger-button form="user-deletion-form">Supprimer mon compte</x-danger-button>
    </x-slot:footer>
</x-dialog>
