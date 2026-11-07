@props([
    'action',
    'method',
    'button',
    'abort',
])

<div class="max-w-xl mx-auto bg-white p-6 bg-white shadow-sm rounded-lg">

    <header>
        <h2 class="text-lg font-semibold">Informations du compte</h2>
        <p class="mt-1 text-sm text-gray-400">Renseignez les informations du compte.</p>
    </header>

    <form method="post" action="{{ $action }}" class="space-y-6">

        @method($method)
        @csrf

        <div>
            <x-input-label for="name" value="Nom"/>
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $account->name ?? '')" autofocus/>
            <x-input-error class="mt-2" :messages="$errors->get('name')"/>
        </div>

        <div>
            <x-input-label for="label" value="Libellé"/>
            <textarea
                id="label"
                rows="4"
                name="label"
                class="resize-none mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-xs"
            >{{ old('label', $account->label ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('label')"/>
        </div>

        <div class="flex items-center gap-5">
            <x-primary-button>{{ $button }}</x-primary-button>
            <a href="{{ $abort }}" class="text-indigo-800 underline hover:no-underline">Annuler</a>
        </div>
    </form>

</div>
