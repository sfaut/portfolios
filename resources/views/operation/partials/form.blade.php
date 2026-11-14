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
            Optional analytic breakdown (<x-analytic-lines>), from the data prepared by
            OperationController::analyticForm(): in creation, the default breakdown of the account.
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
                <x-analytic-lines :form="$analytic_form"/>
            @endif

            <x-input-error :messages="$errors->get('analytic_lines')"/>
        </section>
    </div>

    <div class="flex flex-col gap-1">
        <x-input-label for="delivery_at" value="Date effective"/>
        <x-text-input type="date" id="delivery_at" name="delivery_at" class="w-full" :value="old('delivery_at', $operation->delivery_at ?? date('Y-m-d'))"/>
        <x-input-error :messages="$errors->get('delivery_at')"/>
    </div>

    {{-- Pointage, au sens que l'utilisateur lui donne ; le champ caché envoie 0 quand la case est décochée. --}}
    <div class="flex flex-col gap-1">
        <input type="hidden" name="is_ticked" value="0">
        <label for="is_ticked" class="inline-flex cursor-pointer items-center gap-2 self-start">
            <input
                type="checkbox"
                id="is_ticked"
                name="is_ticked"
                value="1"
                @checked((bool) old('is_ticked', $operation->is_ticked ?? false))
                class="size-4 rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
            >
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pointée</span>
        </label>
        <x-input-error :messages="$errors->get('is_ticked')"/>
    </div>
</form>
