{{--
    Carte d'un compte dans la liste du portfolio, estompée s'il est archivé (<x-link-card>). Le solde
    est l'élément marquant : il porte son signe et sa couleur, comme le solde cumulé de la liste des
    opérations (rouge négatif, vert nul, bleu positif). Les icônes de ses axes analytiques suivent son
    nom, sa description vient dessous, et ses mesures en bas : période de ses opérations et leur
    nombre.

    @var \App\Models\AccountReport $account
        Compte, avec sa description, son solde, son nombre d'opérations et ses première et dernière
        dates d'opération.
--}}
<x-link-card :href="route('account.show', ['account' => $account])" :archived="$account->deleted_at !== null">
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 flex-col gap-0.5">
            <div class="flex min-w-0 items-center gap-2">
                <h3 class="truncate font-semibold">{{ $account->name }}</h3>
                <x-axis-icons :axes="$account->analyticAxes" class="shrink-0"/>
            </div>
            @if (filled($account->description))
                <p class="line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $account->description }}</p>
            @endif
        </div>
        <div @class([
            'shrink-0', 'text-lg', 'font-semibold', 'whitespace-nowrap', 'tabular-nums',
            'text-red-700 dark:text-red-400' => $account->operation_balance < 0,
            'text-emerald-700 dark:text-emerald-400' => $account->operation_balance == 0,
            'text-blue-700 dark:text-blue-400' => $account->operation_balance > 0,
        ])>{{ Number::amount($account->operation_balance, signed: true, currency: 'EUR') }}</div>
    </div>

    <x-slot:stats>
        @if ($account->operation_count > 0)
            <x-link-card.stat icon="calendar">
                {{ $account->operation_first_at->format('d/m/Y') }} – {{ $account->operation_last_at->format('d/m/Y') }}
            </x-link-card.stat>
            <x-link-card.stat icon="receipt">
                {{ trans_choice(':count opération|:count opérations', $account->operation_count) }}
            </x-link-card.stat>
        @else
            <x-link-card.stat icon="receipt">Aucune opération</x-link-card.stat>
        @endif
    </x-slot:stats>
</x-link-card>
