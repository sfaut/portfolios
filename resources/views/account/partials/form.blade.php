{{--
    Formulaire d'un compte, partagé par create et edit : nom, description, puis sa répartition
    analytique par défaut, ordonnée (lignes axe et pourcentage, par JavaScript).

    @var string $action URL d'envoi du formulaire.
    @var string $method Méthode HTTP (post, put).
    @var \App\Models\Account|null $account Compte modifié, absent en création.
    @var array{axes: list<array{id: int, name: string, archived: bool, icon: string}>, lines: list<array{analytic_axis_id: string, percentage: string}>} $analytic_form
        Axes proposés et lignes initiales (AccountController::analyticForm()).
    @var string $axes_index_url Page des axes du portfolio, proposée quand il n'en a aucun.
--}}
<form id="account-form" method="post" action="{{ $action }}" class="flex flex-col gap-6">

    @method($method)
    @csrf

    <div class="flex flex-col gap-1">
        <x-input-label for="name" value="Nom"/>
        <x-text-input id="name" name="name" type="text" class="w-full" :value="old('name', $account->name ?? '')"/>
        <x-input-error :messages="$errors->get('name')"/>
    </div>

    <div class="flex flex-col gap-1">
        <x-input-label for="description" value="Description"/>
        <x-textarea id="description" name="description" class="w-full">{{ old('description', $account->description ?? '') }}</x-textarea>
        <x-input-error :messages="$errors->get('description')"/>
    </div>

    {{--
        Répartition analytique par défaut (<x-analytic-lines>, réordonnable), proposée aux nouvelles
        opérations du compte ; son ordre est celui des axes du compte partout où ils s'affichent.
    --}}
    <section aria-labelledby="analytic-axes-title" class="flex flex-col gap-3">
        <div class="flex items-center gap-1.5">
            <h3 id="analytic-axes-title" class="text-sm font-medium text-gray-700 dark:text-gray-300">Répartition analytique par défaut</h3>

            @if ($analytic_form['axes'] !== [])
                <x-tooltip class="text-gray-400 hover:text-indigo-700 dark:text-gray-500 dark:hover:text-indigo-400">
                    <x-lucide-circle-help class="size-4" aria-hidden="true"/>
                    <span class="sr-only">Aide</span>

                    <x-slot:content>Répartition proposée aux nouvelles opérations du compte, en pourcentages totalisant 100 %. L'ordre des lignes, à régler par leur poignée, est celui où s'affichent les axes du compte.</x-slot:content>
                </x-tooltip>
            @endif
        </div>

        @if ($analytic_form['axes'] === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Le portfolio n'a pas encore d'axe analytique.
                <a href="{{ $axes_index_url }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Créer un axe</a>
            </p>
        @else
            <x-analytic-lines :form="$analytic_form" name="analytic_axes" :amount="false" sortable/>
        @endif

        <x-input-error :messages="$errors->get('analytic_axes')"/>
    </section>
</form>
