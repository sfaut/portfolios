{{-- Carte d'un compte (AccountReport) dans la liste du portfolio, estompée s'il est archivé. --}}
<a
    href="{{ route('account.show', ['account' => $account]) }}"

    @class([
        'border', 'border-gray-200', 'dark:border-gray-700',
        'group', 'grid', 'grid-cols-[1fr_min-content]', 'gap-2',
        'bg-white', 'dark:bg-gray-800', 'shadow-sm', 'p-4',
        'rounded-md',
        'hover:bg-indigo-700', 'hover:text-white',
        'transition-all', 'duration-200', 'ease-linear', 'hover:scale-102',
        'opacity-50' => ($account->deleted_at !== null),
        'hover:opacity-100' => ($account->deleted_at !== null),
    ])
>
    <div class="truncate">
        <div class="font-semibold truncate">{{ $account->name }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-indigo-200">
            @if ($account->operation_last_at !== null)
                {{ optional($account->operation_first_at)->format('d/m/Y') }}
                ~
                {{ optional($account->operation_last_at)->format('d/m/Y') }}
            @else
                --
            @endif
        </div>
    </div>
    <div @class([
        'text-right',
        'content-center',
        // 'text-red-700' => $account->operation_balance < 0,
        // 'text-emerald-700' => $account->operation_balance == 0,
        // 'text-blue-700' => $account->operation_balance > 0,
    ])>{{ Number::currency($account->operation_balance, 'EUR', 'fr') }}</div>
</a>
