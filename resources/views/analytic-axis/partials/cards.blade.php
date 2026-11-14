{{--
    Cartes d'axes analytiques, actifs ou archivés : chaque carte mène à la modification de l'axe.
    La couleur de l'axe est l'élément marquant, sur la tuile de son icône. Sous le nom et la
    description, deux mesures : les opérations réparties sur l'axe et, s'il y en a, les comptes qui
    l'ont dans leur répartition par défaut. La carte pointillée de création suit les axes actifs.

    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AnalyticAxis> $analytic_axes
        Axes à montrer, avec analytic_lines_count et accounts_count.
    @var \App\Models\Portfolio|null $create_in Portfolio où créer un axe, null sans carte de création.
--}}
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($analytic_axes as $analytic_axis)
        <x-link-card :href="route('analytic-axis.edit', $analytic_axis)" :archived="$analytic_axis->trashed()">
            <div class="flex items-start gap-3">
                <span @class(['flex', 'size-10', 'shrink-0', 'items-center', 'justify-center', 'rounded-lg', $analytic_axis->color->badgeClasses()])>
                    <x-dynamic-component :component="'lucide-'.$analytic_axis->icon" class="size-5" aria-hidden="true"/>
                </span>
                <div class="flex min-w-0 flex-col gap-0.5">
                    <h3 class="truncate font-semibold">{{ $analytic_axis->name }}</h3>
                    @if (filled($analytic_axis->description))
                        <p class="line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $analytic_axis->description }}</p>
                    @endif
                </div>
            </div>

            <x-slot:stats>
                <x-link-card.stat icon="receipt">
                    @if ($analytic_axis->analytic_lines_count > 0)
                        {{ trans_choice(':count opération répartie|:count opérations réparties', $analytic_axis->analytic_lines_count) }}
                    @else
                        Aucune opération répartie
                    @endif
                </x-link-card.stat>
                @if ($analytic_axis->accounts_count > 0)
                    <x-link-card.stat icon="wallet">
                        {{ trans_choice('par défaut dans :count compte|par défaut dans :count comptes', $analytic_axis->accounts_count) }}
                    </x-link-card.stat>
                @endif
            </x-slot:stats>
        </x-link-card>
    @endforeach

    @if ($create_in !== null)
        <x-create-card :href="route('analytic-axis.create', $create_in)">Créer un axe</x-create-card>
    @endif
</div>
