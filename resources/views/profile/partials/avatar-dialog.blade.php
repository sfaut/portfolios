{{--
    Boîte « Photo de profil », dans le formulaire de la photo, dont elle lit l'état Alpine
    (avatarEditor) : une scène carrée et son cercle, qui montrent la caméra en direct (mode camera) ou
    l'image à recadrer (mode crop), et une colonne d'aperçu, d'aide et d'erreurs.

    Le retardateur affiche ses chiffres au centre du cercle, autour duquel un anneau se vide
    (animate-countdown), immobile avec motion-reduce. La région aria-live annonce le compte à rebours
    et la prise de vue.
--}}
<x-dialog id="avatar-dialog" x-ref="dialog" x-on:close="close()" class="sm:max-w-xl">
    <x-slot:title>Photo de profil</x-slot:title>
    <x-slot:description>Importez une image ou prenez-vous en photo, puis cadrez-la dans le cercle.</x-slot:description>

    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
        <div class="flex w-full max-w-72 flex-col gap-3 self-center sm:w-72 sm:shrink-0 sm:self-start">
            <div
                x-ref="stage"
                role="group"
                x-bind:tabindex="mode === 'crop' ? 0 : -1"
                x-bind:aria-label="mode === 'crop' ? 'Zone de recadrage' : 'Image de la caméra'"
                aria-describedby="avatar-crop-keys"
                x-on:pointerdown="grab($event)"
                x-on:pointermove="pull($event)"
                x-on:pointerup="release($event)"
                x-on:pointercancel="release($event)"
                x-on:wheel="mode === 'crop' && ($event.preventDefault(), setZoom(zoom * Math.exp(-$event.deltaY / 500)))"
                x-on:keydown="key($event)"
                x-on:dragover.prevent
                x-on:drop.prevent="drop($event)"
                x-bind:class="mode === 'crop' ? 'cursor-grab active:cursor-grabbing' : ''"
                class="relative aspect-square w-full touch-none overflow-hidden rounded-lg bg-gray-950 select-none focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800"
            >
                <video x-ref="video" x-show="mode === 'camera'" autoplay muted playsinline class="absolute inset-0 size-full -scale-x-100 object-cover"></video>

                <img x-show="mode === 'crop'" x-bind:src="source" alt="" draggable="false" x-bind:style="placement(stage, diameter)" class="pointer-events-none absolute max-w-none">

                <div aria-hidden="true" class="pointer-events-none absolute inset-5 rounded-full shadow-[0_0_0_9999px_rgb(3_7_18/0.6)] ring-2 ring-white"></div>

                <template x-if="countdown > 0">
                    <div aria-hidden="true" class="pointer-events-none absolute inset-5 flex items-center justify-center">
                        <svg viewBox="0 0 100 100" class="absolute inset-0 size-full -rotate-90">
                            <circle cx="50" cy="50" r="49" pathLength="100" fill="none" stroke-dasharray="100" class="animate-countdown stroke-indigo-400 stroke-[2] motion-reduce:animate-none"/>
                        </svg>
                        <span x-text="countdown" class="text-7xl font-semibold text-white tabular-nums drop-shadow-[0_1px_8px_rgb(3_7_18/0.85)]"></span>
                    </div>
                </template>

                <div
                    aria-hidden="true"
                    x-bind:class="flash ? 'opacity-90' : 'opacity-0'"
                    class="pointer-events-none absolute inset-0 bg-white transition-opacity duration-150 motion-reduce:hidden"
                ></div>
            </div>

            <p id="avatar-crop-keys" class="sr-only" x-show="mode === 'crop'">Flèches pour déplacer la photo, plus et moins pour zoomer.</p>
            <p class="sr-only" aria-live="assertive" x-text="announce"></p>

            <div x-show="mode === 'crop'" class="flex items-center gap-2">
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

            <div x-show="mode === 'camera'" class="flex gap-2">
                <x-primary-button type="button" x-on:click="shoot()" x-bind:disabled="!live" icon="camera" class="grow justify-center">
                    Déclencher
                </x-primary-button>

                <x-secondary-button x-on:click="toggleTimer()" x-bind:disabled="!live" x-bind:aria-pressed="countdown > 0" class="justify-center gap-1.5 whitespace-nowrap">
                    <x-lucide-timer class="size-4" aria-hidden="true"/>
                    <span x-show="countdown === 0"><span class="sr-only">Dans</span> 3 s</span>
                    <span x-show="countdown > 0">Arrêter<span class="sr-only"> le retardateur</span></span>
                </x-secondary-button>
            </div>
        </div>

        <div x-show="mode === 'crop'" class="flex flex-col items-center gap-3 sm:items-start">
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

            <button type="button" x-show="origin === 'camera'" x-on:click="openCamera()" class="inline-flex items-center gap-1.5 text-sm text-indigo-700 underline hover:no-underline dark:text-indigo-400">
                <x-lucide-camera class="size-4" aria-hidden="true"/>
                Reprendre une photo
            </button>

            <button type="button" x-show="origin === 'file'" x-on:click="pickFile()" class="inline-flex items-center gap-1.5 text-sm text-indigo-700 underline hover:no-underline dark:text-indigo-400">
                <x-lucide-image-up class="size-4" aria-hidden="true"/>
                Importer une autre image
            </button>
        </div>

        <div x-show="mode === 'camera'" class="flex flex-col gap-4 text-sm">
            <p class="text-gray-600 dark:text-gray-400">La photo est enregistrée telle que les autres vous voient : sans effet miroir.</p>

            <x-alert type="warning" x-show="error !== ''"><span x-text="error"></span></x-alert>

            <div x-show="devices.length > 1" class="flex flex-col gap-1">
                <x-input-label for="avatar-camera" value="Caméra"/>
                <select
                    id="avatar-camera"
                    x-model="deviceId"
                    x-on:change="startCamera(deviceId)"
                    class="rounded-md border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600"
                >
                    <template x-for="device in devices" x-bind:key="device.id">
                        <option x-bind:value="device.id" x-text="device.label"></option>
                    </template>
                </select>
            </div>

            <button type="button" x-on:click="pickFile()" class="inline-flex items-center gap-1.5 self-start text-indigo-700 underline hover:no-underline dark:text-indigo-400">
                <x-lucide-image-up class="size-4" aria-hidden="true"/>
                Importer une image
            </button>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" commandfor="avatar-dialog" command="close" class="text-indigo-700 underline hover:no-underline dark:text-indigo-400">Annuler</button>
        <x-primary-button type="button" x-on:click="save()" x-bind:disabled="saving || mode !== 'crop'">Enregistrer la photo</x-primary-button>
    </x-slot:footer>
</x-dialog>
