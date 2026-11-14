{{--
    Compte et ses opérations, paginées ou toutes à la fois (bouton poussoir « Tout »), avec leur solde
    cumulé et leur répartition analytique. Toutes à la fois, la page s'ouvre sur l'opération modifiée, ou
    à défaut au bas du tableau, sur les plus récentes.

    @var \App\Models\Account $account Compte affiché, archivé ou non.
    @var \Illuminate\Pagination\LengthAwarePaginator<int, \App\Models\OperationReport>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\OperationReport> $operations
        Opérations de la page, ou toutes sans pagination, avec leurs lignes analytiques.
    @var bool $show_all Si toutes les opérations sont affichées, sans pagination.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xl font-semibold">
                    <span>Compte <span class="text-indigo-700 dark:text-indigo-400">{{ $account->name }}</span></span>
                    <x-axis-icons :axes="$account->analyticAxes" focusable icon-class="size-5!" class="gap-1.5"/>
                </h2>
                <x-breadcrumb :links="[route('portfolio.show', $account->portfolio) => 'Comptes']"/>
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
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('account.show', $account) }}">
                    <x-toggle-button name="all" :pressed="$show_all" aria-label="Toutes les opérations, sans pagination">Tout</x-toggle-button>
                </form>
                @if ($show_all)
                    <span class="text-xs text-gray-600 dark:text-gray-300">{{ trans_choice(':count opération|:count opérations', $operations->count()) }}</span>
                @else
                    {{ $operations->links('pagination.narrow') }}
                @endif
            </div>
        </div>

        <div
            @if ($show_all)
                x-data
                x-init="const row = $el.querySelector('[aria-current]'); (row ?? $el).scrollIntoView({ block: row ? 'center' : 'end' })"
            @endif
            class="overflow-hidden rounded-md shadow-sm"
        >
            <table class="w-full **:border-gray-200 dark:**:border-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold uppercase tracking-wide">
                    <tr>
                        <th scope="col" class="border px-3 py-2 text-right">ID</th>
                        <th scope="col" class="border px-3 py-2 whitespace-nowrap">Horodatage écriture</th>
                        <th scope="col" class="border px-3 py-2 whitespace-nowrap">Date effective</th>
                        <th scope="col" class="min-w-44 border px-3 py-2 text-left">Libellé</th>
                        <th scope="col" class="border px-3 py-2 text-right whitespace-nowrap">Montant (€)</th>
                        <th scope="col" class="border px-3 py-2 text-left">Axes</th>
                        <th scope="col" class="border px-3 py-2 text-right whitespace-nowrap">Solde (€)</th>
                        <th scope="col" class="border px-3 py-2 whitespace-nowrap">Pointée ?</th>
                        @unless ($account->trashed())
                            <th scope="col" class="border px-3 py-2">Action</th>
                        @endunless
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 text-sm">
                    @forelse ($operations as $operation)
                        {{-- L'opération qui vient d'être modifiée ressort en indigo, l'accent de l'interface, avec un repère à gauche : le vert reste au message de confirmation. --}}
                        <tr
                            @if ($operation->id === session('updated_operation_id')) aria-current="true" @endif
                            @class([
                                'hover:bg-yellow-100', 'dark:hover:bg-yellow-900/30',
                                'bg-primary-50 dark:bg-primary-950/60' => ($operation->id === session('updated_operation_id')),
                            ])
                        >
                            <td @class([
                                'border', 'px-3', 'py-2', 'text-right', 'tabular-nums',
                                'shadow-[inset_4px_0_0_var(--color-primary-600)] dark:shadow-[inset_4px_0_0_var(--color-primary-400)]' => ($operation->id === session('updated_operation_id')),
                            ])>{{ $operation->id }}</td>
                            <td class="border px-3 py-2 text-center whitespace-nowrap tabular-nums">{{ $operation->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="border px-3 py-2 text-center whitespace-nowrap tabular-nums">{{ date('d/m/Y', strtotime($operation->delivery_at)) }}</td>
                            <td class="border px-3 py-2">{{ $operation->label }}</td>
                            <td class="border px-3 py-2 text-right whitespace-nowrap tabular-nums">{{ Number::amount($operation->amount) }}</td>
                            <td class="border px-3 py-2"><x-analytic-breakdown :operation="$operation"/></td>
                            <td @class([
                                'border px-3 py-2 text-right font-semibold whitespace-nowrap tabular-nums',
                                'text-red-700 dark:text-red-400' => $operation->amount_running_sum < 0,
                                'text-emerald-700 dark:text-emerald-400' => $operation->amount_running_sum == 0,
                                'text-blue-700 dark:text-blue-400' => $operation->amount_running_sum > 0,
                            ])>
                                {{ Number::amount($operation->amount_running_sum, signed: true) }}
                            </td>
                            <td class="relative border px-3 py-2 text-center">
                                <x-tick-box :operation="$operation" :disabled="$account->trashed()"/>
                            </td>
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
                            <td colspan="{{ $account->trashed() ? 8 : 9 }}" class="p-4 text-center">
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
