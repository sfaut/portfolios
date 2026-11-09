{{--
    Sélecteur de date (App\View\Components\DatePicker, composant Alpine datePicker), sur le modèle de
    <x-month-picker> : un déclencheur à l'allure d'un champ, et un popover dans la couche supérieure
    (Échap et clic dehors le ferment), placé sous le déclencheur par le positionnement d'ancre CSS,
    au-dessus ou de l'autre côté faute de place. Le popover a un panneau « un mois, ses jours », en
    semaines à partir du lundi, ou deux en mode range, « Début » et « Fin », côte à côte, empilés sur
    un écran étroit. En mode range, des plages préréglées (« Ce mois »…) peuvent précéder les
    panneaux. Le parent lie la sélection par x-model (x-modelable).

    Validation : $config porte une copie de la restriction (App\Support\DateRestriction), qui ne
    sert qu'à griser les jours interdits. Le serveur contrôle la valeur reçue avec sa propre
    restriction, par la règle App\Rules\DateSelection : voir le docblock de
    App\View\Components\DatePicker.

    @var string $name Nom du champ envoyé.
    @var string $label Libellé accessible du sélecteur.
    @var string $mode Mode de sélection : single, multiple ou range.
    @var string|list<string>|array{start: string, end: string} $selection Sélection normalisée.
    @var string $triggerLabel Libellé initial de la sélection, vide sans sélection.
    @var string $placeholder Texte sans sélection, aussi celui du bouton qui l'efface.
    @var list<array{label: string, start: string, end: string}> $ranges Plages préréglées proposées.
    @var array<string, mixed> $config Configuration du composant Alpine.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine (x-model…).
--}}
<div
    x-data="datePicker(@js($config))"
    x-modelable="value"
    x-id="['date-picker']"
    {{ $attributes->class(['shrink-0']) }}
>
    @if ($mode === 'multiple')
        <template x-for="day in value" x-bind:key="day">
            <input type="hidden" name="{{ $name }}[]" x-bind:value="day">
        </template>
    @elseif ($mode === 'range')
        <input type="hidden" name="{{ $name }}[start]" value="{{ $selection['start'] }}" x-bind:value="value.start">
        <input type="hidden" name="{{ $name }}[end]" value="{{ $selection['end'] }}" x-bind:value="value.end">
    @else
        <input type="hidden" name="{{ $name }}" value="{{ $selection }}" x-bind:value="value">
    @endif

    <div
        x-bind:style="{ anchorName: `--${$id('date-picker')}` }"
        @class([
            'flex items-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-300 focus-within:border-indigo-500 dark:focus-within:border-indigo-600 focus-within:shadow-md',
            'w-48' => $mode !== 'range',
            'w-72' => $mode === 'range',
        ])
    >
        <button
            type="button"
            x-ref="trigger"
            x-bind:popovertarget="$id('date-picker')"
            aria-haspopup="dialog"
            aria-label="{{ $label }} : {{ $triggerLabel !== '' ? $triggerLabel : $placeholder }}"
            x-bind:aria-label="`${@js($label)} : ${display || placeholder}`"
            class="flex min-w-0 grow items-center gap-2 rounded-md py-2 pl-3 pr-1 text-left focus:outline-hidden"
        >
            <x-lucide-calendar-days class="size-4 shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true"/>
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
        x-bind:id="$id('date-picker')"
        x-bind:style="{ positionAnchor: `--${$id('date-picker')}` }"
        popover="auto"
        role="dialog"
        aria-label="Choisir {{ $mode === 'single' ? 'une date' : 'des dates' }}"
        x-on:toggle="onToggle($event)"
        x-on:keydown="onKeydown($event)"
        @class([
            '[position-area:bottom_span-right]', '[position-try-fallbacks:flip-block,flip-inline,flip-block_flip-inline]',
            'inset-auto', 'm-0', 'my-1', 'max-h-[calc(100dvh-2rem)]', 'overflow-y-auto',
            'w-72' => $mode !== 'range',
            'w-[calc(100vw-2rem)] sm:w-[36rem]' => $mode === 'range' && $ranges === [],
            'w-[calc(100vw-2rem)] sm:w-[36rem] lg:w-[46rem]' => $mode === 'range' && $ranges !== [],
            'open:flex', 'flex-col', 'gap-3',
            'rounded-md', 'border-0', 'bg-white', 'dark:bg-gray-800', 'p-3', 'shadow-lg',
            'ring-1', 'ring-gray-200', 'dark:ring-gray-700',
            'text-sm', 'text-gray-700', 'dark:text-gray-200',
            'opacity-0', 'open:opacity-100', 'starting:open:opacity-0',
            'transition-all', 'transition-discrete', 'duration-150', 'ease-out',
            'motion-reduce:transition-none',
        ])
    >
        <div class="flex flex-col gap-4 lg:flex-row">
            @if ($ranges !== [])
                <div
                    role="group"
                    aria-label="Plages préréglées"
                    class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700 pb-3 lg:w-40 lg:shrink-0 lg:flex-col lg:flex-nowrap lg:border-b-0 lg:border-r lg:pb-0 lg:pr-3"
                >
                    <template x-for="preset in presets" x-bind:key="preset.label">
                        <button
                            type="button"
                            x-on:click="choosePreset(preset)"
                            x-bind:aria-pressed="isPreset(preset)"
                            x-text="preset.label"
                            class="rounded-md px-2 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-indigo-950/60 aria-pressed:bg-indigo-100 aria-pressed:font-semibold aria-pressed:text-indigo-900 dark:aria-pressed:bg-indigo-900/60 dark:aria-pressed:text-indigo-100 focus-visible:outline-2 focus-visible:outline-indigo-500"
                        ></button>
                    </template>
                </div>
            @endif

            <div @class(['grid grow gap-4', 'sm:grid-cols-2' => $mode === 'range'])>
                <template x-for="panel in panels" x-bind:key="panel.id">
                    <div
                        x-bind:data-panel="panel.id"
                        x-bind:role="panel.title === '' ? null : 'group'"
                        x-bind:aria-label="panel.title === '' ? null : panel.title"
                        class="flex flex-col gap-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <button
                                type="button"
                                x-on:click="previousMonth(panel.id)"
                                x-bind:disabled="! hasPreviousMonth(panel.id)"
                                aria-label="Mois précédent"
                                class="flex size-8 items-center justify-center rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:outline-indigo-500"
                            >
                                <x-lucide-chevron-left class="size-4" aria-hidden="true"/>
                            </button>
                            <span class="flex items-baseline gap-2" aria-live="polite">
                                <span x-show="panel.title !== ''" x-text="panel.title" class="text-gray-500 dark:text-gray-400"></span>
                                <span class="font-semibold" x-text="title(panel.id)"></span>
                            </span>
                            <button
                                type="button"
                                data-next-month
                                x-on:click="nextMonth(panel.id)"
                                x-bind:disabled="! hasNextMonth(panel.id)"
                                aria-label="Mois suivant"
                                class="flex size-8 items-center justify-center rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:outline-indigo-500"
                            >
                                <x-lucide-chevron-right class="size-4" aria-hidden="true"/>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 gap-y-1 text-center tabular-nums" x-on:mouseleave="hovered = ''">
                            <template x-for="(weekday, index) in weekdays" x-bind:key="index">
                                <span x-text="weekday" class="pb-1 text-xs text-gray-400 dark:text-gray-500" aria-hidden="true"></span>
                            </template>
                            <template x-for="blank in blanks(panel.id)" x-bind:key="`blank-${blank}`">
                                <span aria-hidden="true"></span>
                            </template>
                            <template x-for="cell in cells(panel.id)" x-bind:key="cell.key">
                                <button
                                    type="button"
                                    x-bind:data-day="cell.key"
                                    x-bind:disabled="! cell.active"
                                    x-bind:aria-label="cell.name"
                                    x-bind:aria-pressed="cell.selected"
                                    x-bind:tabindex="cell.key === focusKey(panel.id) ? 0 : -1"
                                    x-bind:class="cellClass(cell)"
                                    x-on:click="choose(panel.id, cell.key)"
                                    x-on:mouseenter="hover(panel.id, cell.key)"
                                    x-on:focus="focused = cell.key; hover(panel.id, cell.key)"
                                    x-text="cell.day"
                                    class="rounded-md py-1.5 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-indigo-500"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <button
                type="button"
                x-on:click="clear(); close()"
                class="rounded-md text-indigo-700 dark:text-indigo-400 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-500"
            >{{ $placeholder !== '' ? $placeholder : 'Effacer' }}</button>

            @if ($mode === 'multiple')
                <button
                    type="button"
                    x-on:click="close()"
                    class="rounded-md font-semibold text-indigo-700 dark:text-indigo-400 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-500"
                >Fermer</button>
            @endif
        </div>
    </div>
</div>
