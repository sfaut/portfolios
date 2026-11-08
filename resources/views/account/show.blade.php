{{--
    Compte et ses opérations, paginées, avec leur solde cumulé et leur repère analytique.

    @var \App\Models\Account $account Compte affiché, archivé ou non.
    @var \Illuminate\Pagination\LengthAwarePaginator<int, \App\Models\OperationReport> $operations Opérations de la page, avec leurs lignes analytiques.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold">Compte <span class="text-indigo-700 dark:text-indigo-400">{{ $account->name }}</span></h2>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    ➜ Portfolio
                    <a href="{{ route('portfolio.show', $account->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $account->portfolio->name }}</a>
                </div>
            </div>
            <x-link.secondary :target="route('account.edit', $account)">Modifier le compte</x-link.secondary>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto flex flex-col gap-6">

        @if (session()->has('alert'))
            <x-alert type="success">
                {{ session('alert') }}
            </x-alert>
        @endif

        @if ($account->trashed())
            <x-alert type="warning">
                Ce compte est archivé : ses opérations sont en lecture seule.
                Il peut être désarchivé depuis « Modifier le compte ».
            </x-alert>
        @endif

        <div @class(['flex', 'justify-between' => ! $account->trashed(), 'justify-end' => $account->trashed()])>
            @unless ($account->trashed())
                <x-link.primary :target="route('operation.create', $account)">Écrire une opération</x-link.primary>
            @endunless
            {{ $operations->links('pagination.narrow') }}
        </div>

        <div class="overflow-hidden rounded-md shadow-sm">
            <table class="w-full **:border-gray-200 dark:**:border-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold uppercase tracking-wide">
                    <tr>
                        <th class="border px-3 py-2">ID</th>
                        <th class="border px-3 py-2 whitespace-nowrap">Date effective</th>
                        <th class="border px-3 py-2">Libellé</th>
                        <th class="border px-3 py-2 whitespace-nowrap">Montant (€)</th>
                        <th class="border px-3 py-2">Analytique</th>
                        <th class="border px-3 py-2 whitespace-nowrap">Solde (€)</th>
                        <th class="border px-3 py-2 whitespace-nowrap">Horodatage création</th>
                        @unless ($account->trashed())
                            <th class="border px-3 py-2">Action</th>
                        @endunless
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
                                {{ Number::amount($operation->amount) }}
                            </td>
                            <td class="border px-3 py-2 text-center whitespace-nowrap">
                                @if ($operation->analyticLines->isNotEmpty())
                                    <x-tooltip>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 dark:bg-indigo-900/50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                                            <span aria-hidden="true">✓</span>
                                            {{ $operation->analyticLines->count() }} {{ Str::plural('axe', $operation->analyticLines->count()) }}
                                        </span>

                                        <x-slot:content>
                                            <span class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-1">
                                                @foreach ($operation->analyticLines as $line)
                                                    <span>{{ $line->analyticAxis->name }}</span>
                                                    <span class="text-right tabular-nums">{{ Number::percentage($line->percentage, maxPrecision: 2, locale: 'fr') }}</span>
                                                @endforeach
                                            </span>
                                        </x-slot:content>
                                    </x-tooltip>
                                @endif
                            </td>
                            <td @class([
                                'border px-3 py-2 text-right font-semibold whitespace-nowrap',
                                'text-red-700 dark:text-red-400' => $operation->amount_running_sum < 0,
                                'text-emerald-700 dark:text-emerald-400' => $operation->amount_running_sum == 0,
                                'text-blue-700 dark:text-blue-400' => $operation->amount_running_sum > 0,
                            ])>
                                {{ Number::amount($operation->amount_running_sum, signed: true) }}
                            </td>
                            <td class="border px-3 py-2 text-center whitespace-nowrap">{{ $operation->created_at?->format('d/m/Y H:i:s') }}</td>
                            @unless ($account->trashed())
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
                            @endunless
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $account->trashed() ? 7 : 8 }}" class="p-4 text-center">
                                Pas encore d'opérations.<br>
                                Mais ça ne saurait tarder...<br>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</x-app-layout>
