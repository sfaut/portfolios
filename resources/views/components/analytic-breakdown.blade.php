{{--
    Répartition analytique d'une opération, écrite dans une cellule de tableau (liste d'un compte,
    Grand livre) : une ligne par axe, dans l'ordre chargé (AnalyticLine::inDisplayOrder(), du plus
    lourd au plus léger), avec l'icône colorée et le nom de l'axe, sa part, puis son montant en gris,
    alignés en colonnes. Un axe seul porte tout le montant, déjà écrit : son nom suffit. Rien pour
    une opération non répartie.

    @var \App\Models\Operation|\App\Models\OperationReport $operation
        Opération, ses lignes analytiques chargées avec leur axe (archivés compris).
--}}
@props([
    'operation',
])

@if ($operation->analyticLines->isNotEmpty())
    <div {{ $attributes->class(['inline-grid grid-cols-[auto_auto_auto] items-center gap-x-3 gap-y-0.5']) }}>
        @foreach ($operation->analyticLines as $line)
            <span @class(['flex items-center gap-1.5', 'col-span-3' => $operation->analyticLines->count() === 1])>
                <x-axis-badge :axis="$line->analyticAxis" icon-only/>
                <span class="max-w-48 shrink-0 truncate">{{ $line->analyticAxis->name }}</span>
                @if ($line->analyticAxis->trashed())
                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">(archivé)</span>
                @endif
            </span>
            @if ($operation->analyticLines->count() > 1)
                <span class="text-right whitespace-nowrap tabular-nums">{{ Number::percentage($line->percentage, maxPrecision: 2, locale: 'fr') }}</span>
                <span class="text-right whitespace-nowrap tabular-nums text-gray-500 dark:text-gray-400">{{ Number::amount($line->amountShare($operation->amount), currency: 'EUR') }}</span>
            @endif
        @endforeach
    </div>
@endif
