<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold">Compte <span class="text-indigo-700 dark:text-indigo-400">{{ $account->name }}</span></h2>
                <div class="text-sm text-gray-400">
                    ➜ Portfolio
                    <a href="{{ route('portfolio.show', $account->portfolio) }}" class="text-indigo-400 underline hover:no-underline">{{ $account->portfolio->name }}</a>
                </div>
            </div>
            <div class="text-sm flex text-indigo-300 hover:text-indigo-700 dark:text-indigo-500 dark:hover:text-indigo-300 transition-colors ease-linear delay-100">
                <a href="{{ route('account.show', $account) }}" class="px-2 underline hover:no-underline">Annuler</a>
            </div>
        </div>
    </x-slot>

    @include('account.partials.form', [
        'action' => route('account.update', $account),
        'method' => 'put',
        'button' => 'Modifier',
        'abort' => route('account.show', $account),
    ])

    <div @class([
        'max-w-xl', 'mx-auto',
        'shadow-sm', 'rounded-md',
        'p-6', 'bg-white', 'dark:bg-gray-800',
        'ring-2', 'ring-red-800', 'dark:ring-red-600',
        'mt-6',
    ])>
        <header>
            <h2 class="text-lg font-semibold">Archivage du compte</h2>
            <p class="mt-1 text-sm text-gray-400">
                Toutes les opérations du compte seront également archivées.
                Le compte et ses opérations pourront être désarchivés.
            </p>
        </header>
        <form
            method="post"
            action="{{ route('account.destroy', $account) }}"
            onsubmit="return confirm('Êtes-vous sûr et certain de vouloir archiver ce compte ?')"
        >
            @method('delete')
            @csrf

            <div class="flex items-center gap-6">
                <x-danger-button>
                    Archiver
                </x-danger-button>
                <a href="{{ route('account.show', $account) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
            </div>
        </form>
    </div>

</x-app-layout>
