<form
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

    <div class="flex flex-col gap-1">
        <x-input-label for="delivery_at" value="Date effective"/>
        <input type="date" id="delivery_at" name="delivery_at" value="{{ old('delivery_at', $operation->delivery_at ?? date('Y-m-d')) }}" class="rounded-md w-full border border-slate-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
        <x-input-error :messages="$errors->get('delivery_at')"/>
    </div>

    <div class="flex items-center gap-6">
        <x-primary-button>{{ $button }}</x-primary-button>
        <a href="{{ $abort }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
    </div>
</form>
