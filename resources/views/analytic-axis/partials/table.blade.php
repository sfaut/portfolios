{{-- Tableau d'axes analytiques ($analytic_axes, avec analytic_lines_count), actifs ou archivés. --}}
<div class="overflow-hidden rounded-md shadow-sm">
    <table class="w-full **:border-gray-200 dark:**:border-gray-700">
        <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold uppercase tracking-wide">
            <tr>
                <th class="border px-3 py-2">Nom</th>
                <th class="border px-3 py-2">Description</th>
                <th class="border px-3 py-2 whitespace-nowrap">Opérations réparties</th>
                <th class="border px-3 py-2">Action</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 text-sm">
            @foreach ($analytic_axes as $analytic_axis)
                <tr class="hover:bg-yellow-100 dark:hover:bg-yellow-900/30">
                    <td class="border px-3 py-2 font-semibold">{{ $analytic_axis->name }}</td>
                    <td class="border px-3 py-2 text-gray-500 dark:text-gray-400">{{ filled($analytic_axis->description) ? $analytic_axis->description : '--' }}</td>
                    <td class="border px-3 py-2 text-right">{{ $analytic_axis->analytic_lines_count }}</td>
                    <td class="border px-3 py-2 text-center">
                        <a
                            href="{{ route('analytic-axis.edit', $analytic_axis) }}"
                            class="text-xs font-semibold uppercase text-indigo-700 dark:text-indigo-400 no-underline hover:underline"
                        >
                            Modifier
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
