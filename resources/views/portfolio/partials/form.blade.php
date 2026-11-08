<form method="post" action="{{ $action }}" class="flex flex-col gap-6">

    @method($method)
    @csrf

    <div>
        <x-input-label for="name" value="Nom"/>
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $portfolio->name ?? '')" autofocus/>
        <x-input-error class="mt-2" :messages="$errors->get('name')"/>
    </div>

    <div>
        <x-input-label for="description" value="Description"/>
        <textarea
            id="description"
            rows="4"
            name="description"
            class="resize-none mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-xs"
        >{{ old('description', $portfolio->description ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')"/>
    </div>

    <div class="flex items-center gap-6">
        <x-primary-button>{{ $button }}</x-primary-button>
        <a href="{{ $abort }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
    </div>
</form>
