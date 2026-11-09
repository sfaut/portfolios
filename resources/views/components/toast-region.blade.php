{{--
    Région des toasts (App\View\Components\ToastRegion), menée par le store Alpine `toasts`
    (resources/js/toasts.js), posée une fois par la mise en page.

    La région est un popover manuel qui couvre l'écran sans retenir le pointeur : placée dans la
    couche supérieure, et remontée à chaque toast, elle passe au-dessus des boîtes de dialogue et
    des en-têtes collants. Elle tient une pile par place, où le plus récent est au plus près du bord,
    et deux régions vivantes qui annoncent les messages : alert pour un danger, status sinon. Un toast
    ne prend jamais le focus ; Échap ferme celui qui l'a. Sans JavaScript, le flash de session
    s'affiche en bas à droite, sans fermeture.

    @var string $position Place des toasts qui n'en donnent pas.
    @var array<string, array{icon: string, color: string}> $types Icône et couleur de chaque type.
    @var array{type: string, message: string}|null $flash Toast du flash de session `toast`.
--}}
<div
    x-data
    x-init="$store.toasts.mount($el, @js($position), @js($flash))"
    popover="manual"
    role="region"
    aria-label="Notifications"
    class="pointer-events-none inset-0 m-0 size-full max-h-none max-w-none overflow-visible border-0 bg-transparent p-0"
>
    <div role="status" class="sr-only" x-text="$store.toasts.polite"></div>
    <div role="alert" class="sr-only" x-text="$store.toasts.assertive"></div>

    <template x-for="position in $store.toasts.positions" :key="position">
        <ol
            class="fixed left-4 right-4 flex flex-col gap-2 sm:w-96"
            x-bind:class="$store.toasts.stackClass(position)"
            x-show="$store.toasts.at(position).length > 0"
        >
            <template x-for="toast in $store.toasts.at(position)" :key="toast.id">
                <li
                    class="pointer-events-auto relative overflow-hidden rounded-md bg-gray-950 text-sm text-gray-100 shadow-lg starting:opacity-0 transition-all duration-150 ease-out motion-reduce:transition-none dark:ring-1 dark:ring-gray-700"
                    x-bind:class="{
                        'opacity-0': toast.leaving,
                        'starting:translate-y-2': ! toast.position.startsWith('top-'),
                        'starting:-translate-y-2': toast.position.startsWith('top-'),
                    }"
                    x-on:mouseenter="$store.toasts.hold(toast.id, 'hovered', true)"
                    x-on:mouseleave="$store.toasts.hold(toast.id, 'hovered', false)"
                    x-on:focusin="$store.toasts.hold(toast.id, 'focused', true)"
                    x-on:focusout="$el.contains($event.relatedTarget) || $store.toasts.hold(toast.id, 'focused', false)"
                    x-on:keydown.escape.stop="$store.toasts.dismiss(toast.id)"
                >
                    <div class="flex items-start gap-3 py-3 pr-2 pl-4">
                        @foreach ($types as $type => $style)
                            <span x-show="toast.type === @js($type)" class="flex h-5 shrink-0 items-center">
                                <x-dynamic-component :component="$style['icon']" @class(['size-5', $style['color']]) aria-hidden="true"/>
                            </span>
                        @endforeach

                        <p class="min-w-0 flex-1 leading-5 break-words" x-text="toast.message"></p>

                        <template x-if="toast.action">
                            <button
                                type="button"
                                class="-my-1 shrink-0 rounded px-2 py-1 font-medium text-indigo-300 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-400"
                                x-text="toast.action.label"
                                x-on:click="$store.toasts.run(toast.id)"
                            ></button>
                        </template>

                        <button
                            type="button"
                            aria-label="Fermer la notification"
                            class="-my-1 shrink-0 rounded p-1 text-gray-400 hover:text-gray-100 focus-visible:outline-2 focus-visible:outline-indigo-400"
                            x-on:click="$store.toasts.dismiss(toast.id)"
                        >
                            <x-lucide-x class="size-4" aria-hidden="true"/>
                        </button>
                    </div>

                    <div
                        x-show="toast.duration > 0"
                        class="absolute inset-x-0 bottom-0 h-0.5 origin-left animate-toast-time bg-gray-500 motion-reduce:animate-none"
                        x-bind:style="{
                            animationDuration: `${toast.duration}ms`,
                            animationPlayState: $store.toasts.isPaused(toast) ? 'paused' : 'running',
                        }"
                        aria-hidden="true"
                    ></div>
                </li>
            </template>
        </ol>
    </template>
</div>

@if ($flash)
    <noscript>
        <div
            role="{{ $flash['type'] === 'danger' ? 'alert' : 'status' }}"
            class="fixed right-4 bottom-4 left-4 z-50 flex items-start gap-3 rounded-md bg-gray-950 px-4 py-3 text-sm text-gray-100 shadow-lg sm:left-auto sm:w-96 dark:ring-1 dark:ring-gray-700"
        >
            <x-dynamic-component :component="$types[$flash['type']]['icon']" @class(['size-5', 'shrink-0', $types[$flash['type']]['color']]) aria-hidden="true"/>
            <p class="min-w-0 flex-1 leading-5 break-words">{{ $flash['message'] }}</p>
        </div>
    </noscript>
@endif
