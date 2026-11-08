{{--
    Sélecteur de mois (App\View\Components\MonthPicker, composant Alpine monthPicker) : un déclencheur
    à l'allure d'un champ, et un panneau « une année, douze mois » dans la couche supérieure
    (popover auto : Échap et clic dehors le ferment), placé sous le déclencheur par le positionnement
    d'ancre CSS, au-dessus faute de place. Le parent lie la sélection par x-model (x-modelable).

    @var string $name Nom du champ envoyé.
    @var string $label Libellé accessible du sélecteur.
    @var string $mode Mode de sélection : single, multiple ou range.
    @var string|list<string>|array{start: string, end: string} $selection Sélection normalisée.
    @var string $triggerLabel Libellé initial de la sélection, vide sans sélection.
    @var string $placeholder Texte sans sélection, aussi celui du bouton qui l'efface.
    @var array<string, mixed> $config Configuration du composant Alpine.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine (x-model…).
--}}
<div
    x-data="monthPicker(@js($config))"
    x-modelable="value"
    x-id="['month-picker']"
    {{ $attributes->class(['shrink-0']) }}
>
    @if ($mode === 'multiple')
        <template x-for="month in value" x-bind:key="month">
            <input type="hidden" name="{{ $name }}[]" x-bind:value="month">
        </template>
    @elseif ($mode === 'range')
        <input type="hidden" name="{{ $name }}[start]" value="{{ $selection['start'] }}" x-bind:value="value.start">
        <input type="hidden" name="{{ $name }}[end]" value="{{ $selection['end'] }}" x-bind:value="value.end">
    @else
        <input type="hidden" name="{{ $name }}" value="{{ $selection }}" x-bind:value="value">
    @endif

    <div
        x-bind:style="{ anchorName: `--${$id('month-picker')}` }"
        class="flex w-52 items-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-300 focus-within:border-indigo-500 dark:focus-within:border-indigo-600 focus-within:shadow-md"
    >
        <button
            type="button"
            x-ref="trigger"
            x-bind:popovertarget="$id('month-picker')"
            aria-haspopup="dialog"
            aria-label="{{ $label }} : {{ $triggerLabel !== '' ? $triggerLabel : $placeholder }}"
            x-bind:aria-label="`${@js($label)} : ${display || placeholder}`"
            class="flex min-w-0 grow items-center gap-2 rounded-md py-2 pl-3 pr-1 text-left focus:outline-hidden"
        >
            <x-lucide-calendar class="size-4 shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true"/>
            <span
                x-text="display || placeholder"
                x-bind:class="{ 'text-gray-400 dark:text-gray-500': display === '' }"
                @class(['truncate', 'text-gray-400 dark:text-gray-500' => $triggerLabel === ''])
            >{{ $triggerLabel !== '' ? $triggerLabel : $placeholder }}</span>
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
        <span
            x-show="empty"
            @style(['display: none' => $triggerLabel !== ''])
            class="pointer-events-none flex shrink-0 items-center px-3 text-gray-400 dark:text-gray-500"
            aria-hidden="true"
        >
            <x-lucide-chevron-down class="size-4"/>
        </span>
    </div>

    <div
        x-ref="panel"
        x-bind:id="$id('month-picker')"
        x-bind:style="{ positionAnchor: `--${$id('month-picker')}` }"
        popover="auto"
        role="dialog"
        aria-label="Choisir {{ $mode === 'single' ? 'un mois' : 'des mois' }}"
        x-on:toggle="onToggle($event)"
        x-on:keydown="onKeydown($event)"
        @class([
            '[position-area:bottom_span-right]', '[position-try-fallbacks:flip-block]',
            'inset-auto', 'm-0', 'my-1', 'w-72',
            'open:flex', 'flex-col', 'gap-3',
            'rounded-md', 'border-0', 'bg-white', 'dark:bg-gray-800', 'p-3', 'shadow-lg',
            'ring-1', 'ring-gray-200', 'dark:ring-gray-700',
            'text-sm', 'text-gray-700', 'dark:text-gray-200',
            'opacity-0', 'open:opacity-100', 'starting:open:opacity-0',
            'transition-all', 'transition-discrete', 'duration-150', 'ease-out',
            'motion-reduce:transition-none',
        ])
    >
        <div class="flex items-center justify-between">
            <button
                type="button"
                x-on:click="previousYear()"
                x-bind:disabled="! hasPreviousYear"
                aria-label="Année précédente"
                class="flex size-8 items-center justify-center rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:outline-indigo-500"
            >
                <x-lucide-chevron-left class="size-4" aria-hidden="true"/>
            </button>
            <span class="text-base font-semibold tabular-nums" aria-live="polite" x-text="year"></span>
            <button
                type="button"
                x-ref="nextYear"
                x-on:click="nextYear()"
                x-bind:disabled="! hasNextYear"
                aria-label="Année suivante"
                class="flex size-8 items-center justify-center rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:outline-indigo-500"
            >
                <x-lucide-chevron-right class="size-4" aria-hidden="true"/>
            </button>
        </div>

        <div class="grid grid-cols-3 gap-1" x-on:mouseleave="hovered = ''">
            <template x-for="cell in cells" x-bind:key="cell.key">
                <button
                    type="button"
                    x-bind:data-month="cell.key"
                    x-bind:disabled="! cell.active"
                    x-bind:aria-label="cell.name"
                    x-bind:aria-pressed="cell.selected"
                    x-bind:tabindex="cell.key === focusKey ? 0 : -1"
                    x-bind:class="cellClass(cell)"
                    x-on:click="choose(cell.key)"
                    x-on:mouseenter="hovered = cell.key"
                    x-on:focus="focused = cell.key; hovered = cell.key"
                    x-text="cell.short"
                    class="rounded-md py-2 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-indigo-500"
                ></button>
            </template>
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
