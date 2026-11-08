<form method="post" action="{{ $action }}" class="flex flex-col gap-6">

    @method($method)
    @csrf

    <div class="flex flex-col gap-1">
        <x-input-label for="name" value="Nom"/>
        <x-text-input id="name" name="name" type="text" class="w-full" :value="old('name', $account->name ?? '')" autofocus/>
        <x-input-error :messages="$errors->get('name')"/>
    </div>

    <div class="flex flex-col gap-1">
        <x-input-label for="label" value="Libellé"/>
        <x-textarea id="label" name="label" class="w-full">{{ old('label', $account->label ?? '') }}</x-textarea>
        <x-input-error :messages="$errors->get('label')"/>
    </div>

    <div class="flex items-center gap-6">
        <x-primary-button>{{ $button }}</x-primary-button>
        <a href="{{ $abort }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
    </div>
</form>
