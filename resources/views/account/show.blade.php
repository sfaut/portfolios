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
            <x-link.secondary :target="route('account.edit', $account)">Modifier le compte</x-link.secondary>
        </div>
    </x-slot>

    @if (session()->has('alert'))
        <x-alert type="success" class="max-w-6xl mx-auto mb-6">
            {{ session('alert') }}
        </x-alert>
    @endif

    <div class="max-w-6xl mx-auto mb-6 flex justify-between">
        <x-link.primary :target="route('operation.create', $account)">Écrire une opération</x-link.primary>
        {{ $operations->links('pagination.narrow') }}
    </div>

    <div class="max-w-6xl mx-auto rounded-lg shadow-sm mt-6 mb-6">
        <table class="w-full **:border-gray-200 dark:**:border-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold uppercase tracking-wide">
                <tr>
                    <th class="border px-3 py-2">ID</th>
                    <th class="border px-3 py-2 whitespace-nowrap">Date effective</th>
                    <th class="border px-3 py-2">Libellé</th>
                    <th class="border px-3 py-2 whitespace-nowrap">Montant (€)</th>
                    <th class="border px-3 py-2 whitespace-nowrap">Solde (€)</th>
                    <th class="border px-3 py-2 whitespace-nowrap">Horodatage création</th>
                    <th class="border px-3 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 text-sm">
                @forelse ($operations as $operation)
                    <tr
                        @class([
                            'hover:bg-yellow-100', 'dark:hover:bg-yellow-900/30',
                            'bg-success-200 dark:bg-success-900/60' => ($operation->id === session('updated_operation_id'))
                        ])
                    >
                        <td class="border px-3 py-2 text-right">{{ $operation->id }}</td>
                        <td class="border px-3 py-2 text-center">{{ date('d/m/Y', strtotime($operation->delivery_at)) }}</td>
                        <td class="border px-3 py-2">{{ $operation->label }}</td>
                        <td class="border px-3 py-2 text-right whitespace-nowrap">
                            {{ Number::format($operation->amount, precision: 2, locale: 'fr') }}
                        </td>
                        <td @class([
                            'border px-3 py-2 text-right font-semibold whitespace-nowrap',
                            'text-red-700 dark:text-red-400' => $operation->amount_running_sum < 0,
                            'text-emerald-700 dark:text-emerald-400' => $operation->amount_running_sum == 0,
                            'text-blue-700 dark:text-blue-400' => $operation->amount_running_sum > 0,
                        ])>
                            {{ Number::format($operation->amount_running_sum, precision: 2, locale: 'fr') }}
                        </td>
                        <td class="border px-3 py-2 text-center whitespace-nowrap">{{ $operation->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td class="border px-3 py-2 text-center">
                            <a
                                href="{{ route('operation.edit', $operation) }}"
                                @class([
                                    'text-xs', 'font-semibold', 'uppercase', 'text-indigo-700', 'dark:text-indigo-400',
                                    'no-underline', 'hover:underline',
                                ])
                            >
                                Modifier
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center">
                            Pas encore d'opérations.<br>
                            Mais ça ne saurait tarder...<br>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-app-layout>
