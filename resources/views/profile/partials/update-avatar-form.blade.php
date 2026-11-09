{{--
    Photo de profil : l'avatar en grand, puis le choix d'une photo, recadrée dans une boîte (composant
    Alpine avatarCropper), et son retrait. Sans JavaScript, la photo part telle quelle par le bouton
    « Envoyer la photo », et le serveur la recadre au centre. Une adresse non vérifiée n'envoie pas
    de photo.

    @var \App\Models\User $user Utilisateur connecté.
    @var string $avatar_accept Types MIME acceptés, pour l'attribut accept du champ.
    @var string $avatar_formats Formats et poids acceptés, écrits sous le bouton.
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
                    x-data="avatarCropper"
                    class="flex flex-col gap-1"
                >
                    @csrf
                    @method('put')

                    <label class="inline-flex cursor-pointer items-center gap-2 self-start rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase shadow-xs transition duration-150 ease-in-out hover:bg-gray-50 has-focus-visible:ring-2 has-focus-visible:ring-indigo-500 has-focus-visible:ring-offset-2 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:has-focus-visible:ring-offset-gray-800">
                        <x-lucide-image-up class="size-4" aria-hidden="true"/>
                        {{ $user->avatar_path === '' ? 'Choisir une photo' : 'Changer la photo' }}
                        <input
                            type="file"
                            name="avatar"
                            accept="{{ $avatar_accept }}"
                            aria-describedby="avatar-formats"
                            x-ref="input"
                            x-on:change="choose($event)"
                            class="sr-only"
                        >
                    </label>

                    <p id="avatar-formats" class="text-sm text-gray-600 dark:text-gray-400">{{ $avatar_formats }}</p>

                    <x-input-error :messages="$errors->get('avatar')"/>

                    <noscript>
                        <x-primary-button>Envoyer la photo</x-primary-button>
                    </noscript>

                    <x-dialog id="avatar-crop" x-ref="dialog" x-on:close="cancel()" class="sm:max-w-xl">
                        <x-slot:title>Recadrer la photo</x-slot:title>
                        <x-slot:description>Glissez la photo pour la placer dans le cercle, et zoomez pour la cadrer.</x-slot:description>

                        <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                            <div class="flex w-full max-w-72 flex-col gap-3 self-center">
                                <div
                                    x-ref="stage"
                                    tabindex="0"
                                    role="group"
                                    aria-label="Zone de recadrage"
                                    aria-describedby="avatar-crop-keys"
                                    x-on:pointerdown="grab($event)"
                                    x-on:pointermove="pull($event)"
                                    x-on:pointerup="release($event)"
                                    x-on:pointercancel="release($event)"
                                    x-on:wheel.prevent="setZoom(zoom * Math.exp(-$event.deltaY / 500))"
                                    x-on:keydown="key($event)"
                                    class="relative aspect-square w-full cursor-grab touch-none overflow-hidden rounded-lg bg-gray-950 select-none focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 active:cursor-grabbing dark:focus-visible:ring-offset-gray-800"
                                >
                                    <img x-bind:src="source" alt="" draggable="false" x-bind:style="placement(stage, diameter)" class="pointer-events-none absolute max-w-none">
                                    <div aria-hidden="true" class="pointer-events-none absolute inset-5 rounded-full shadow-[0_0_0_9999px_rgb(3_7_18/0.6)] ring-2 ring-white"></div>
                                </div>

                                <p id="avatar-crop-keys" class="sr-only">Flèches pour déplacer la photo, plus et moins pour zoomer.</p>

                                <div class="flex items-center gap-2">
                                    <button type="button" x-on:click="setZoom(zoom - 0.25)" class="rounded-full p-1 text-gray-500 hover:text-gray-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-gray-400 dark:hover:text-gray-100">
                                        <x-lucide-zoom-out class="size-5" aria-hidden="true"/>
                                        <span class="sr-only">Dézoomer</span>
                                    </button>

                                    <input
                                        type="range"
                                        min="1"
                                        step="0.01"
                                        aria-label="Zoom"
                                        x-bind:max="maxZoom"
                                        x-bind:value="zoom"
                                        x-on:input="setZoom(Number($event.target.value))"
                                        class="min-w-0 grow accent-indigo-600 dark:accent-indigo-400"
                                    >

                                    <button type="button" x-on:click="setZoom(zoom + 0.25)" class="rounded-full p-1 text-gray-500 hover:text-gray-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-gray-400 dark:hover:text-gray-100">
                                        <x-lucide-zoom-in class="size-5" aria-hidden="true"/>
                                        <span class="sr-only">Zoomer</span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col items-center gap-3 sm:items-start">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Aperçu</p>

                                <div class="flex items-end gap-4" aria-hidden="true">
                                    <div class="relative size-32 overflow-hidden rounded-full bg-gray-950 ring-1 ring-black/5 dark:ring-white/10">
                                        <img x-bind:src="source" alt="" x-bind:style="placement(128, 128)" class="absolute max-w-none">
                                    </div>
                                    <div class="relative size-8 overflow-hidden rounded-full bg-gray-950 ring-1 ring-black/5 dark:ring-white/10">
                                        <img x-bind:src="source" alt="" x-bind:style="placement(32, 32)" class="absolute max-w-none">
                                    </div>
                                </div>

                                <button type="button" x-on:click="recenter()" class="inline-flex items-center gap-1.5 text-sm text-indigo-700 underline hover:no-underline dark:text-indigo-400">
                                    <x-lucide-locate-fixed class="size-4" aria-hidden="true"/>
                                    Recentrer
                                </button>
                            </div>
                        </div>

                        <x-slot:footer>
                            <button type="button" commandfor="avatar-crop" command="close" class="text-indigo-700 underline hover:no-underline dark:text-indigo-400">Annuler</button>
                            <x-primary-button type="button" x-on:click="save()" x-bind:disabled="saving">Enregistrer la photo</x-primary-button>
                        </x-slot:footer>
                    </x-dialog>
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
