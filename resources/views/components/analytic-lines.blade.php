{{--
    Éditeur d'une répartition analytique (composant Alpine analyticLines,
    resources/js/analytic-lines.js) : une ligne par axe (icône, axe, pourcentage), le total et
    « Ajouter une ligne analytique ». Partagé par le formulaire d'une opération et celui d'un compte,
    il prend les erreurs de ses lignes par clef (<name>.<i>.<champ>).

    Les lignes et le total partagent les mêmes colonnes : poignée (sortable), axe (qui s'étire),
    pourcentage, part du montant (amount), emplacement du bouton de retrait ; le total se place ainsi
    sous les pourcentages.

    @var array{axes: list<array{id: int, name: string, archived: bool, icon: string}>, lines: list<array{analytic_axis_id: string, percentage: string}>} $form
        Axes proposés et lignes initiales, préparés par le contrôleur.
    @var string $name Nom du champ des lignes (analytic_lines, analytic_axes).
    @var bool $amount Si chaque ligne montre sa part du champ Montant du formulaire.
    @var bool $sortable Si les lignes se réordonnent, par leur poignée ou ses flèches.
    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation.
--}}
@props([
    'form',
    'name' => 'analytic_lines',
    'amount' => true,
    'sortable' => false,
])

<div
    x-data="analyticLines(@js($form), @js((object) $errors->getBag('default')->getMessages()), @js(['name' => $name, 'amount' => $amount]))"
    {{ $attributes->class('flex flex-col gap-2 text-sm') }}
>
    <div @if ($sortable) x-sort="move($item, $position)" @endif class="flex flex-col gap-2">
        <template x-for="(line, index) in lines" x-bind:key="line.key">
            <div @if ($sortable) x-sort:item="line.key" @endif x-bind:data-line-key="line.key" class="flex flex-col gap-1 rounded-md bg-white dark:bg-gray-800">
                <div class="flex items-center gap-2">
                    @if ($sortable)
                        <button
                            type="button"
                            x-sort:handle
                            data-line-handle
                            x-on:keydown.up.prevent="moveBy(index, -1)"
                            x-on:keydown.down.prevent="moveBy(index, 1)"
                            x-bind:aria-label="`Déplacer la ligne ${index + 1} (flèches haut et bas)`"
                            class="flex size-9 shrink-0 cursor-grab touch-none items-center justify-center rounded-md text-gray-400 dark:text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-300 active:cursor-grabbing"
                        >
                            <x-lucide-grip-vertical class="size-5 stroke-[1.5]" aria-hidden="true"/>
                        </button>
                    @endif

                    <span class="flex size-5 shrink-0 items-center justify-center" x-html="iconOf(line.analytic_axis_id)" aria-hidden="true"></span>
                    <select
                        x-model="line.analytic_axis_id"
                        x-bind:name="fieldName(index, 'analytic_axis_id')"
                        x-bind:aria-label="`Axe analytique de la ligne ${index + 1}`"
                        class="min-w-0 grow rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 py-1.5 text-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:shadow-md"
                    >
                        <option value="">Choisir un axe…</option>
                        <template x-for="axis in axes" x-bind:key="axis.id">
                            <option
                                x-bind:value="String(axis.id)"
                                x-bind:selected="String(axis.id) === line.analytic_axis_id"
                                x-bind:disabled="isTaken(axis.id, index)"
                                x-text="axis.archived ? `${axis.name} (archivé)` : axis.name"
                            ></option>
                        </template>
                    </select>

                    <div class="flex shrink-0 items-center gap-1">
                        <x-number-input
                            x-model="line.percentage"
                            x-on:keydown.enter.prevent="addFromKeyboard()"
                            x-bind:name="fieldName(index, 'percentage')"
                            x-bind:aria-label="`Pourcentage de la ligne ${index + 1}`"
                            min="0.01"
                            max="100"
                            class="w-24 py-1.5 text-right text-sm tabular-nums"
                        />
                        <span class="text-gray-500 dark:text-gray-400">%</span>
                    </div>

                    @if ($amount)
                        <span
                            x-text="formatAmount(lineAmount(line))"
                            class="w-24 shrink-0 text-right tabular-nums text-gray-500 dark:text-gray-400"
                        ></span>
                    @endif

                    <button
                        type="button"
                        x-on:click="remove(index)"
                        x-bind:aria-label="`Retirer la ligne ${index + 1}`"
                        class="flex size-9 shrink-0 items-center justify-center rounded-md text-gray-400 dark:text-gray-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                    >
                        <x-lucide-circle-x class="size-5 stroke-[1.5]" aria-hidden="true"/>
                    </button>
                </div>

                <ul x-show="lineErrors(index).length > 0" class="flex flex-col gap-1 text-red-600 dark:text-red-400">
                    <template x-for="message in lineErrors(index)">
                        <li x-text="message"></li>
                    </template>
                </ul>
            </div>
        </template>
    </div>

    <div x-show="lines.length > 0" class="flex items-center gap-2">
        <p
            x-bind:class="complete ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"
            class="flex min-w-0 grow items-center justify-end gap-3 whitespace-nowrap"
            aria-live="polite"
        >
            <span
                x-show="! complete"
                x-text="total < 10000 ? `reste ${format(10000 - total)} %` : `excédent ${format(total - 10000)} %`"
                class="text-xs"
            ></span>
            <span class="font-semibold">Total</span>
            <span class="sr-only" x-text="`${format(total)} %`"></span>
        </p>

        <div
            x-bind:class="complete ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"
            class="flex shrink-0 items-center gap-1 font-semibold"
            aria-hidden="true"
        >
            <span class="w-24 px-3 text-right tabular-nums" x-text="format(total)"></span>
            <span>%</span>
        </div>

        @if ($amount)
            <span class="w-24 shrink-0" aria-hidden="true"></span>
        @endif
        <span class="size-9 shrink-0" aria-hidden="true"></span>
    </div>

    <div>
        <x-secondary-button x-on:click="add()" x-bind:disabled="! canAdd">
            Ajouter une ligne analytique
        </x-secondary-button>
    </div>
</div>
