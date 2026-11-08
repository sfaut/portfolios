<div
    @class([
        'max-w-xl', 'mx-auto',
        'rounded-md',
        'bg-white', 'dark:bg-gray-800',
        'mb-6', 'p-6',
    ])
>
    <header>
        <h2 class="text-lg font-semibold">Informations de l'opération</h2>
        <p class="mt-1 text-sm text-gray-400">Renseignez les informations de l'opération.</p>
    </header>

    <form
        method="post"
        action="{{ $action }}"
        class="flex flex-col gap-6"
    >

        @method($method)

        @csrf

        <div>
            <x-input-label for="label" value="Libellé"/>
            <x-text-input id="label" name="label" class="mt-1 block w-full" :value="old('label', $operation->label ?? '')" autofocus/>
            <x-input-error class="mt-2" :messages="$errors->get('label')"/>
        </div>

        <div>
            <x-input-label for="amount" value="Montant"/>
            <x-number-input id="amount" name="amount" class="mt-1 block w-full" :value="old('amount', $operation->amount ?? '')"/>
            <x-input-error class="mt-2" :messages="$errors->get('amount')"/>
        </div>

        <div>
            <x-input-label for="delivery_at" value="Date effective"/>
            <input type="date" id="delivery_at" name="delivery_at" value="{{ old('delivery_at', $operation->delivery_at ?? date('Y-m-d')) }}" class="mt-1 rounded-md w-full border border-slate-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <x-input-error class="mt-2" :messages="$errors->get('delivery_at')"/>
        </div>

        <div class="flex items-center gap-5">
            <x-primary-button>{{ $button }}</x-primary-button>
            <a href="{{ $abort }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
        </div>
    </form>
</div>
