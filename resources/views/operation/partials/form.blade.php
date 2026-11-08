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
        <x-text-input id="label" name="label" class="w-full" :value="old('label', $operation->label ?? '')" autofocus/>
        <x-input-error :messages="$errors->get('label')"/>
    </div>

    <div class="flex flex-col gap-1">
        <x-input-label for="amount" value="Montant"/>
        <x-number-input id="amount" name="amount" class="w-full" :value="old('amount', $operation->amount ?? '')"/>
        <x-input-error :messages="$errors->get('amount')"/>
    </div>

    {{--
        Optional analytic breakdown (resources/js/analytic-lines.js), from the data prepared by
        OperationController::analyticForm(). The component picks the errors of its lines by key.
    --}}
    <section aria-labelledby="analytic-lines-title" class="flex flex-col gap-3 rounded-md border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center gap-1.5">
            <h3 id="analytic-lines-title" class="font-medium text-sm">Répartition analytique</h3>

            <x-tooltip class="text-gray-400 hover:text-indigo-700 dark:text-gray-500 dark:hover:text-indigo-400">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>
                </svg>
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
                class="flex flex-col gap-3"
            >
                <template x-for="(line, index) in lines" x-bind:key="line.key">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <select
                                x-model="line.analytic_axis_id"
                                x-bind:name="`analytic_lines[${index}][analytic_axis_id]`"
                                x-bind:aria-label="`Axe analytique de la ligne ${index + 1}`"
                                class="min-w-0 grow rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:shadow-md"
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
                                    x-bind:name="`analytic_lines[${index}][percentage]`"
                                    x-bind:aria-label="`Pourcentage de la ligne ${index + 1}`"
                                    min="0.01"
                                    max="100"
                                    class="w-28 text-right"
                                />
                                <span class="text-sm text-gray-500 dark:text-gray-400">%</span>
                            </div>

                            <button
                                type="button"
                                x-on:click="remove(index)"
                                x-bind:aria-label="`Retirer la ligne ${index + 1}`"
                                title="Retirer la ligne"
                                class="shrink-0 rounded-md p-2 text-gray-500 dark:text-gray-400 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <ul x-show="lineErrors(index).length > 0" class="text-sm flex flex-col gap-1 text-red-600 dark:text-red-400">
                            <template x-for="message in lineErrors(index)">
                                <li x-text="message"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-secondary-button x-on:click="add()" x-bind:disabled="! canAdd">
                        Ajouter une ligne analytique
                    </x-secondary-button>

                    <p
                        x-show="lines.length > 0"
                        x-bind:class="complete ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"
                        class="text-sm font-semibold"
                        aria-live="polite"
                    >
                        Total : <span x-text="format(total)"></span>
                        <span x-show="! complete" class="font-normal">
                            (<span x-text="total < 10000 ? `reste ${format(10000 - total)}` : `excédent ${format(total - 10000)}`"></span>)
                        </span>
                    </p>
                </div>
            </div>
        @endif

        <x-input-error :messages="$errors->get('analytic_lines')"/>
    </section>

    <div class="flex flex-col gap-1">
        <x-input-label for="delivery_at" value="Date effective"/>
        <x-text-input type="date" id="delivery_at" name="delivery_at" class="w-full" :value="old('delivery_at', $operation->delivery_at ?? date('Y-m-d'))"/>
        <x-input-error :messages="$errors->get('delivery_at')"/>
    </div>
</form>
