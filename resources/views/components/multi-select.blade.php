{{--
    Sélection multiple (App\View\Components\MultiSelect, composant Alpine multiSelect) : un
    déclencheur à l'allure d'un champ, qui résume la sélection, et un popover de cases à cocher
    natives, dans la couche supérieure (Échap et clic dehors le ferment), placé sous le déclencheur
    par le positionnement d'ancre CSS. Le déclencheur ouvre le popover sans JavaScript
    (popovertarget), et les cases cochées partent avec le formulaire dans name[]. Le parent lie la
    sélection par x-model (x-modelable).

    @var string $name Nom du champ envoyé (name[]).
    @var string $label Libellé accessible du sélecteur.
    @var list<array{value: string, label: string, hint: string}> $choices Options proposées.
    @var list<string> $selection Valeurs cochées.
    @var string $triggerLabel Libellé initial de la sélection, vide sans sélection.
    @var string $placeholder Texte sans option cochée.
    @var string $popoverId Identifiant du popover et nom de son ancre.
    @var bool $searchable Si le popover propose un champ de recherche.
    @var array<string, mixed> $config Configuration du composant Alpine.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine (x-model…).
--}}
<div
    x-data="multiSelect(@js($config))"
    x-modelable="value"
    {{ $attributes->class(['shrink-0']) }}
>
    <div
        style="anchor-name: --{{ $popoverId }}"
        class="flex w-52 items-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-300 focus-within:border-indigo-500 dark:focus-within:border-indigo-600 focus-within:shadow-md"
    >
        <button
            type="button"
            x-ref="trigger"
            popovertarget="{{ $popoverId }}"
            aria-haspopup="dialog"
            aria-label="{{ $label }} : {{ $triggerLabel !== '' ? $triggerLabel : $placeholder }}"
            x-bind:aria-label="`${@js($label)} : ${display || placeholder}`"
            class="flex min-w-0 grow items-center gap-2 rounded-md py-2 pl-3 pr-1 text-left focus:outline-hidden"
        >
            <span
                x-text="display || placeholder"
                x-bind:class="{ 'text-gray-400 dark:text-gray-500': display === '' }"
                @class(['grow', 'truncate', 'text-gray-400 dark:text-gray-500' => $triggerLabel === ''])
            >{{ $triggerLabel !== '' ? $triggerLabel : $placeholder }}</span>
            <span
                x-show="empty"
                @style(['display: none' => $triggerLabel !== ''])
                class="flex shrink-0 items-center pr-2 text-gray-400 dark:text-gray-500"
                aria-hidden="true"
            >
                <x-lucide-chevron-down class="size-4"/>
            </span>
        </button>

        <button
            type="button"
            x-show="! empty"
            x-on:click="clear()"
            @style(['display: none' => $triggerLabel === ''])
            aria-label="Effacer : {{ $label }}"
            class="flex shrink-0 items-center rounded-md px-3 py-2 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 focus-visible:outline-2 focus-visible:outline-indigo-500"
        >
            <x-lucide-x class="size-4" aria-hidden="true"/>
        </button>
    </div>

    <div
        x-ref="panel"
        id="{{ $popoverId }}"
        style="position-anchor: --{{ $popoverId }}"
        popover="auto"
        role="dialog"
        aria-label="{{ $label }}"
        x-on:toggle="onToggle($event)"
        x-on:keydown="onKeydown($event)"
        @class([
            '[position-area:bottom_span-right]', '[position-try-fallbacks:flip-block,flip-inline,flip-block_flip-inline]',
            'inset-auto', 'm-0', 'my-1', 'w-72', 'max-w-[calc(100vw-2rem)]',
            'open:flex', 'flex-col', 'gap-2',
            'rounded-md', 'border-0', 'bg-white', 'dark:bg-gray-800', 'p-3', 'shadow-lg',
            'ring-1', 'ring-gray-200', 'dark:ring-gray-700',
            'text-sm', 'text-gray-700', 'dark:text-gray-200',
            'opacity-0', 'open:opacity-100', 'starting:open:opacity-0',
            'transition-all', 'transition-discrete', 'duration-150', 'ease-out',
            'motion-reduce:transition-none',
        ])
    >
        @if ($searchable)
            <x-text-input
                type="search"
                x-ref="search"
                x-model="query"
                x-on:keydown.enter.prevent
                placeholder="Rechercher…"
                aria-label="Rechercher dans : {{ $label }}"
                class="w-full text-sm"
            />
        @endif

        <ul class="flex max-h-64 flex-col overflow-y-auto">
            @foreach ($choices as $choice)
                <li x-show="matches(@js($choice['label']))">
                    <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 has-focus-visible:outline-2 has-focus-visible:outline-indigo-500">
                        <input
                            type="checkbox"
                            name="{{ $name }}[]"
                            value="{{ $choice['value'] }}"
                            x-model="value"
                            @checked(in_array($choice['value'], $selection, strict: true))
                            class="rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-indigo-600 focus:ring-0 focus:ring-offset-0"
                        >
                        @if ($choice['icon'] !== '')
                            <x-dynamic-component :component="'lucide-'.$choice['icon']" :class="Arr::toCssClasses(['size-4', 'shrink-0', $choice['iconClass']])" aria-hidden="true"/>
                        @endif
                        <span class="min-w-0 grow truncate">{{ $choice['label'] }}</span>
                        @if ($choice['hint'] !== '')
                            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $choice['hint'] }}</span>
                        @endif
                    </label>
                </li>
            @endforeach
        </ul>

        @if ($searchable)
            <p x-show="noMatch" style="display: none" class="px-2 py-1.5 text-gray-500 dark:text-gray-400">Aucune option ne correspond.</p>
        @endif

        <div class="flex items-center justify-end gap-4 border-t border-gray-200 dark:border-gray-700 pt-2">
            <button
                type="button"
                x-on:click="clear()"
                class="rounded-md text-indigo-700 dark:text-indigo-400 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-500"
            >{{ $placeholder !== '' ? $placeholder : 'Effacer' }}</button>
            <button
                type="button"
                popovertarget="{{ $popoverId }}"
                popovertargetaction="hide"
                class="rounded-md font-semibold text-indigo-700 dark:text-indigo-400 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-500"
            >Fermer</button>
        </div>
    </div>
</div>
