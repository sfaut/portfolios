<form id="analytic-axis-form" method="post" action="{{ $action }}" class="flex flex-col gap-6">

    @method($method)
    @csrf

    <div class="flex flex-col gap-1">
        <x-input-label for="name" value="Nom"/>
        <x-text-input id="name" name="name" class="w-full" maxlength="50" :value="old('name', $analytic_axis->name ?? '')" autofocus/>
        <x-input-error :messages="$errors->get('name')"/>
    </div>

    <div class="flex flex-col gap-1">
        <x-input-label for="description" value="Description"/>
        <x-textarea id="description" name="description" class="w-full" maxlength="1000">{{ old('description', $analytic_axis->description ?? '') }}</x-textarea>
        <x-input-error :messages="$errors->get('description')"/>
    </div>
</form>
