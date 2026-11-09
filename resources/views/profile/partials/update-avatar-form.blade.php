{{--
    Photo de profil : l'avatar en grand, deux entrées vers la même boîte (profile.partials.avatar-dialog,
    composant Alpine avatarEditor), « Importer une photo » et « Prendre une photo », puis le retrait.

    Les boutons qui demandent JavaScript portent hidden, qu'Alpine retire ; « Prendre une photo » ne
    paraît que si le navigateur ouvre une caméra. Sans JavaScript, le <noscript> donne un champ de
    fichier et « Envoyer la photo », et le serveur recadre la photo au centre. Une adresse non vérifiée
    n'envoie pas de photo.

    @var \App\Models\User $user Utilisateur connecté.
    @var string $avatar_accept Types MIME acceptés, pour l'attribut accept des champs.
    @var string $avatar_formats Formats et poids acceptés, écrits sous les boutons.
    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
<x-card>
    <x-slot:title>Photo</x-slot:title>
    <x-slot:description>Elle vous représente dans la barre du haut. À défaut, vos initiales s'affichent.</x-slot:description>

    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
        <x-avatar :user="$user" size="lg"/>

        <div class="flex flex-col gap-3">
            @if ($user->hasVerifiedEmail())
                <form
                    id="avatar-form"
                    method="post"
                    action="{{ route('profile.avatar.update') }}"
                    enctype="multipart/form-data"
                    x-data="avatarEditor"
                    class="flex flex-col gap-1"
                >
                    @csrf
                    @method('put')

                    <input
                        type="file"
                        accept="{{ $avatar_accept }}"
                        x-ref="input"
                        x-bind:name="'avatar'"
                        x-on:change="choose($event)"
                        class="hidden"
                    >

                    <div class="flex flex-wrap gap-2">
                        <x-secondary-button hidden x-bind:hidden="false" x-on:click="pickFile()" aria-describedby="avatar-formats" class="gap-2">
                            <x-lucide-image-up class="size-4" aria-hidden="true"/>
                            Importer une photo
                        </x-secondary-button>

                        <x-secondary-button hidden x-bind:hidden="!cameraAvailable" x-on:click="openCamera()" class="gap-2">
                            <x-lucide-camera class="size-4" aria-hidden="true"/>
                            Prendre une photo
                        </x-secondary-button>
                    </div>

                    <noscript>
                        <div class="flex flex-col items-start gap-2">
                            <x-input-label for="avatar" value="Photo"/>
                            <input id="avatar" type="file" name="avatar" accept="{{ $avatar_accept }}" aria-describedby="avatar-formats" class="text-sm">
                            <x-primary-button>Envoyer la photo</x-primary-button>
                        </div>
                    </noscript>

                    <p id="avatar-formats" class="text-sm text-gray-600 dark:text-gray-400">{{ $avatar_formats }}</p>

                    <x-input-error :messages="$errors->get('avatar')"/>

                    @include('profile.partials.avatar-dialog')
                </form>

                @if ($user->avatar_path !== '')
                    <form method="post" action="{{ route('profile.avatar.destroy') }}">
                        @csrf
                        @method('delete')

                        <button type="submit" class="inline-flex items-center gap-1.5 text-sm text-red-700 underline hover:no-underline dark:text-red-400">
                            <x-lucide-trash-2 class="size-4" aria-hidden="true"/>
                            Retirer la photo
                        </button>
                    </form>
                @endif
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">Vérifiez votre adresse pour ajouter une photo.</p>
            @endif

            @if (session('status') === 'avatar-updated')
                <p class="text-sm text-gray-600 dark:text-gray-400">Photo enregistrée.</p>
            @elseif (session('status') === 'avatar-deleted')
                <p class="text-sm text-gray-600 dark:text-gray-400">Photo retirée.</p>
            @endif
        </div>
    </div>
</x-card>
