<section class="flex flex-col items-start gap-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Suppression de compte
        </h2>

        <p class="mt-1 text-sm text-gray-400">
            Une fois votre compte supprimé, toutes ses ressources et données seront définitivement perdues.
            Avant de supprimer votre compte, veuillez télécharger les données
            ou informations que vous souhaitez conserver.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Supprimer</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>

        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @method('delete')
            @csrf

            <h2 class="text-lg font-medium">
                Êtes-vous certain de vouloir supprimer votre compte ?
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Une fois votre compte supprimé, toutes ses ressources et données seront définitivement perdues.
                Veuillez saisir votre mot de passe pour confirmer la suppression définitive du compte.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Mot de passe" class="sr-only"/>

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Mot de passe"
               />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2"/>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
