<x-card>
    <x-slot:title>Suppression de compte</x-slot:title>
    <x-slot:description>
        Une fois votre compte supprimé, toutes ses ressources et données seront définitivement perdues.
        Avant de supprimer votre compte, veuillez télécharger les données
        ou informations que vous souhaitez conserver.
    </x-slot:description>

    <x-slot:footer>
        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >Supprimer</x-danger-button>

        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>

            <form method="post" action="{{ route('profile.destroy') }}" class="flex flex-col gap-6 p-6">
                @method('delete')
                @csrf

                <header class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold">
                        Êtes-vous certain de vouloir supprimer votre compte ?
                    </h2>

                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Une fois votre compte supprimé, toutes ses ressources et données seront définitivement perdues.
                        Veuillez saisir votre mot de passe pour confirmer la suppression définitive du compte.
                    </p>
                </header>

                <div class="flex flex-col gap-1">
                    <x-input-label for="password" value="Mot de passe" class="sr-only"/>

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="w-3/4"
                        placeholder="Mot de passe"
                   />

                    <x-input-error :messages="$errors->userDeletion->get('password')"/>
                </div>

                <div class="flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button>
                        {{ __('Delete Account') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    </x-slot:footer>
</x-card>
