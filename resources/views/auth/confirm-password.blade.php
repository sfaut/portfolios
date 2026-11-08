<x-guest-layout>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-4">
        @csrf

        <!-- Password -->
        <div class="flex flex-col gap-1">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex justify-end">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
