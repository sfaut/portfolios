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
        <x-text-input type="date" id="delivery_at" name="delivery_at" class="w-full" :value="old('delivery_at', $operation->delivery_at ?? date('Y-m-d'))"/>
        <x-input-error :messages="$errors->get('delivery_at')"/>
    </div>
</form>
