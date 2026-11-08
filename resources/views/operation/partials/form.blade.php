{{--
    Formulaire d'une opération, partagé par create et edit, avec sa répartition analytique facultative.

    @var string $action URL d'envoi du formulaire.
    @var string $method Méthode HTTP (post, put).
    @var \App\Models\Account|null $account Compte de l'opération, en création.
    @var \App\Models\Operation|null $operation Opération modifiée, en modification.
    @var array{axes: list<array{id: int, name: string, archived: bool}>, lines: list<array{analytic_axis_id: string, percentage: string}>} $analytic_form
        Axes proposés et lignes initiales (OperationController::analyticForm()).
    @var \Illuminate\Support\ViewErrorBag $errors Erreurs de validation ; celles des lignes vont au composant Alpine.
--}}
<form
    id="operation-form"
    method="post"
    action="{{ $action }}"
    class="flex flex-col gap-6"
>

    @method($method)

    @csrf

    <div class="flex flex-col gap-1">
        <x-input-label for="label" value="Libellé"/>
        <x-text-input id="label" name="label" class="w-full" :value="old('label', $operation->label ?? '')"/>
        <x-input-error :messages="$errors->get('label')"/>
    </div>

    {{--
        Le montant et sa répartition analytique, qui en dépend : la répartition se range sous le
        champ, en retrait derrière un filet vertical, comme une branche du montant.
    --}}
    <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1">
            <x-input-label for="amount" value="Montant"/>
            <x-number-input id="amount" name="amount" class="w-full" :value="old('amount', $operation->amount ?? '')"/>
            <x-input-error :messages="$errors->get('amount')"/>
        </div>

        {{--
            Optional analytic breakdown (resources/js/analytic-lines.js), from the data prepared by
            OperationController::analyticForm(). The component picks the errors of its lines by key.
            The lines and the total share the same columns: axis (grows), percentage, part of the
            amount, then the slot of the remove button, so that the total sits under the percentages.
        --}}
        <section aria-labelledby="analytic-lines-title" class="flex flex-col gap-3 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
            <div class="flex items-center gap-1.5">
                <h3 id="analytic-lines-title" class="font-medium text-sm">Répartition analytique</h3>

                <x-tooltip class="text-gray-400 hover:text-indigo-700 dark:text-gray-500 dark:hover:text-indigo-400">
                    <x-lucide-circle-help class="size-4" aria-hidden="true"/>
                    <span class="sr-only">Aide</span>

                    <x-slot:content>Répartissez le montant entre des axes, en pourcentages totalisant 100 %.</x-slot:content>
                </x-tooltip>
            </div>

            @if ($analytic_form['axes'] === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Ce portfolio n'a pas encore d'axe analytique.
                    <a href="{{ route('analytic-axis.create', ($account ?? $operation->account)->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Créer un axe</a>
                </p>
            @else
                <div
                    x-data="analyticLines(@js($analytic_form), @js((object) $errors->getBag('default')->getMessages()))"
                    class="flex flex-col gap-2 text-sm"
                >
                    <template x-for="(line, index) in lines" x-bind:key="line.key">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <select
                                    x-model="line.analytic_axis_id"
                                    x-bind:name="`analytic_lines[${index}][analytic_axis_id]`"
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
                                        x-bind:name="`analytic_lines[${index}][percentage]`"
                                        x-bind:aria-label="`Pourcentage de la ligne ${index + 1}`"
                                        min="0.01"
                                        max="100"
                                        class="w-24 py-1.5 text-right text-sm tabular-nums"
                                    />
                                    <span class="text-gray-500 dark:text-gray-400">%</span>
                                </div>

                                <span
                                    x-text="formatAmount(lineAmount(line))"
                                    class="w-24 shrink-0 text-right tabular-nums text-gray-500 dark:text-gray-400"
                                ></span>

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

                    <div x-show="lines.length > 0" class="flex items-center gap-2">
                        <p
                            x-bind:class="complete ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"
                            class="flex min-w-0 grow items-center justify-end gap-3 whitespace-nowrap"
                            aria-live="polite"
                        >
                            <span
                                x-show="! complete"
                                x-text="total < 10000 ? `reste ${format(10000 - total)}\u00a0%` : `excédent ${format(total - 10000)}\u00a0%`"
                                class="text-xs"
                            ></span>
                            <span class="font-semibold">Total</span>
                            <span class="sr-only" x-text="`${format(total)}\u00a0%`"></span>
                        </p>

                        <div
                            x-bind:class="complete ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"
                            class="flex shrink-0 items-center gap-1 font-semibold"
                            aria-hidden="true"
                        >
                            <span class="w-24 px-3 text-right tabular-nums" x-text="format(total)"></span>
                            <span>%</span>
                        </div>

                        <span class="w-24 shrink-0" aria-hidden="true"></span>
                        <span class="size-9 shrink-0" aria-hidden="true"></span>
                    </div>

                    <div>
                        <x-secondary-button x-on:click="add()" x-bind:disabled="! canAdd">
                            Ajouter une ligne analytique
                        </x-secondary-button>
                    </div>
                </div>
            @endif

            <x-input-error :messages="$errors->get('analytic_lines')"/>
        </section>
    </div>

    <div class="flex flex-col gap-1">
        <x-input-label for="delivery_at" value="Date effective"/>
        <x-text-input type="date" id="delivery_at" name="delivery_at" class="w-full" :value="old('delivery_at', $operation->delivery_at ?? date('Y-m-d'))"/>
        <x-input-error :messages="$errors->get('delivery_at')"/>
    </div>
</form>
