{{--
    Cartes d'axes analytiques, actifs ou archivés : chaque carte mène à la modification de l'axe.
    La couleur de l'axe est l'élément marquant, sur la tuile de son icône ; le reste reprend la
    carte d'un compte. Sous le nom et la description, deux mesures : les opérations réparties sur
    l'axe et, s'il y en a, les comptes qui l'ont dans leur répartition par défaut. La carte
    pointillée de création suit les axes actifs.

    @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AnalyticAxis> $analytic_axes
        Axes à montrer, avec analytic_lines_count et accounts_count.
    @var \App\Models\Portfolio|null $create_in Portfolio où créer un axe, null sans carte de création.
--}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach ($analytic_axes as $analytic_axis)
        <a
            href="{{ route('analytic-axis.edit', $analytic_axis) }}"
            @class([
                'flex', 'flex-col', 'justify-between', 'gap-3',
                'rounded-md', 'border', 'border-gray-200', 'dark:border-gray-700',
                'bg-white', 'dark:bg-gray-800', 'p-4', 'shadow-sm',
                'hover:border-indigo-500', 'dark:hover:border-indigo-400', 'hover:shadow-md',
                'focus-visible:outline-2', 'focus-visible:outline-offset-2', 'focus-visible:outline-indigo-500',
                'transition', 'duration-150', 'ease-linear',
            ])
        >
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

            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5">
                    <x-lucide-receipt class="size-3.5 shrink-0" aria-hidden="true"/>
                    @if ($analytic_axis->analytic_lines_count > 0)
                        {{ trans_choice(':count opération répartie|:count opérations réparties', $analytic_axis->analytic_lines_count) }}
                    @else
                        Aucune opération répartie
                    @endif
                </span>
                @if ($analytic_axis->accounts_count > 0)
                    <span class="inline-flex items-center gap-1.5">
                        <x-lucide-wallet class="size-3.5 shrink-0" aria-hidden="true"/>
                        {{ trans_choice('par défaut dans :count compte|par défaut dans :count comptes', $analytic_axis->accounts_count) }}
                    </span>
                @endif
            </div>
        </a>
    @endforeach

    @if ($create_in !== null)
        <a
            href="{{ route('analytic-axis.create', $create_in) }}"
            @class([
                'flex', 'flex-col', 'justify-center', 'gap-1',
                'rounded-md', 'bg-white', 'dark:bg-gray-800', 'shadow-sm', 'hover:shadow-md',
                'p-4',
                'border-2', 'border-dashed', 'border-gray-400', 'dark:border-gray-600',
                'hover:border-indigo-700', 'hover:text-indigo-700', 'dark:hover:border-indigo-400', 'dark:hover:text-indigo-400',
                'text-center', 'text-gray-400', 'dark:text-gray-500',
                'transition-all', 'duration-200', 'ease-linear', 'hover:scale-102',
            ])
        >
            <x-lucide-circle-plus class="mx-auto size-7.5 stroke-[1.25]" aria-hidden="true"/>
            <h3>Créer un axe</h3>
        </a>
    @endif
</div>
