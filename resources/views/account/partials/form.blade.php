{{--
    Formulaire d'un compte, partagé par create et edit : nom, description, puis ses axes analytiques
    en pastilles à cocher (cases natives, sans JavaScript), atténuées tant qu'elles ne sont pas cochées.

    @var string $action URL d'envoi du formulaire.
    @var string $method Méthode HTTP (post, put).
    @var \App\Models\Account|null $account Compte modifié, absent en création.
    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AnalyticAxis> $analytic_axes Axes proposés.
    @var list<int> $checked_axis_ids Axes cochés.
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

    <fieldset class="flex flex-col gap-2">
        <legend class="mb-1 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
            Axes analytiques

            @if ($analytic_axes->isNotEmpty())
                <x-tooltip class="font-normal text-gray-400 hover:text-indigo-700 dark:text-gray-500 dark:hover:text-indigo-400">
                    <x-lucide-circle-help class="size-4" aria-hidden="true"/>
                    <span class="sr-only">Aide</span>

                    <x-slot:content>Cochez les axes qui caractérisent le compte : leurs icônes s'afficheront à côté de son nom.</x-slot:content>
                </x-tooltip>
            @endif
        </legend>

        @if ($analytic_axes->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Le portfolio n'a pas encore d'axe analytique.
                <a href="{{ $axes_index_url }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Créer un axe</a>
            </p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($analytic_axes as $axis)
                    <label class="cursor-pointer rounded-full">
                        <input type="checkbox" name="analytic_axes[]" value="{{ $axis->id }}" @checked(in_array($axis->id, $checked_axis_ids, strict: true)) class="peer sr-only">
                        <x-axis-badge
                            :axis="$axis"
                            class="opacity-45 grayscale transition hover:opacity-75 peer-checked:opacity-100 peer-checked:grayscale-0 peer-checked:ring-2 peer-checked:ring-current peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-indigo-500 motion-reduce:transition-none"
                        />
                    </label>
                @endforeach
            </div>
        @endif

        <x-input-error :messages="Arr::flatten([$errors->get('analytic_axes'), $errors->get('analytic_axes.*')])"/>
    </fieldset>
</form>
