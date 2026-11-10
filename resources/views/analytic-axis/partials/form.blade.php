{{--
    Formulaire d'un axe analytique, partagé par create et edit.

    @var string $action URL d'envoi du formulaire.
    @var string $method Méthode HTTP (post, put).
    @var \App\Models\AnalyticAxis|null $analytic_axis Axe modifié, absent en création.
--}}
<form id="analytic-axis-form" method="post" action="{{ $action }}" class="flex flex-col gap-6">

    @method($method)
    @csrf

    <div class="flex flex-col gap-1">
        <x-input-label for="name" value="Nom"/>
        <x-text-input id="name" name="name" class="w-full" maxlength="50" :value="old('name', $analytic_axis->name ?? '')"/>
        <x-input-error :messages="$errors->get('name')"/>
    </div>

    <div class="flex flex-col gap-1">
        <x-input-label for="description" value="Description"/>
        <x-textarea id="description" name="description" class="w-full" maxlength="1000">{{ old('description', $analytic_axis->description ?? '') }}</x-textarea>
        <x-input-error :messages="$errors->get('description')"/>
    </div>

    <div class="flex flex-col gap-1">
        <x-axis-appearance
            name-field="name"
            :name="old('name', $analytic_axis->name ?? '')"
            :icon="old('icon', $analytic_axis->icon ?? \App\Support\AxisIcons::DEFAULT)"
            :color="old('color', $analytic_axis?->color->value ?? '')"
        />
        <x-input-error :messages="[...$errors->get('icon'), ...$errors->get('color')]"/>
    </div>
</form>
