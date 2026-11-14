{{--
    Modification d'un axe analytique, avec son archivage ou, s'il est archivé, son désarchivage.

    @var \App\Models\AnalyticAxis $analytic_axis Axe modifié, archivé ou non.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold">Axe analytique <span class="text-indigo-700 dark:text-indigo-400">{{ $analytic_axis->name }}</span></h2>
            <x-breadcrumb :links="[route('analytic-axis.index', $analytic_axis->portfolio) => 'Axes analytiques']"/>
        </div>
    </x-slot>

    <div class="max-w-2xl flex flex-col gap-6">

        <x-card>
            <x-slot:title>Informations de l'axe</x-slot:title>
            <x-slot:description>Modifiez les informations de l'axe analytique.</x-slot:description>

            @include('analytic-axis.partials.form', [
                'action' => route('analytic-axis.update', $analytic_axis),
                'method' => 'put',
            ])

            <x-slot:footer>
                <a href="{{ route('analytic-axis.index', $analytic_axis->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                <x-primary-button form="analytic-axis-form" icon="check">Modifier</x-primary-button>
            </x-slot:footer>
        </x-card>

        @if ($analytic_axis->trashed())
            <x-card>
                <x-slot:title>Désarchivage de l'axe</x-slot:title>
                <x-slot:description>
                    L'axe est archivé : il ne se choisit plus pour répartir une opération.
                    Une fois désarchivé, il retrouve sa place parmi les axes du portfolio.
                </x-slot:description>

                <x-slot:footer>
                    <form method="post" action="{{ route('analytic-axis.restore', $analytic_axis) }}" class="flex items-center gap-6">
                        @method('patch')
                        @csrf

                        <a href="{{ route('analytic-axis.index', $analytic_axis->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                        <x-primary-button>Désarchiver</x-primary-button>
                    </form>
                </x-slot:footer>
            </x-card>
        @else
            <x-card danger>
                <x-slot:title>Archivage de l'axe</x-slot:title>
                <x-slot:description>
                    L'axe ne se choisira plus pour répartir une opération ; les opérations déjà réparties
                    le conservent. Il pourra être désarchivé.
                </x-slot:description>

                <x-slot:footer>
                    <a href="{{ route('analytic-axis.index', $analytic_axis->portfolio) }}" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</a>
                    <x-danger-button type="button" commandfor="archive-analytic-axis" command="show-modal">Archiver</x-danger-button>
                </x-slot:footer>
            </x-card>

            <x-dialog id="archive-analytic-axis" danger>
                <x-slot:title>Archiver l'axe « {{ $analytic_axis->name }} » ?</x-slot:title>
                <x-slot:description>
                    Il pourra être désarchivé à tout moment depuis cette page.
                </x-slot:description>

                <x-slot:footer>
                    <button type="button" commandfor="archive-analytic-axis" command="close" class="text-indigo-700 dark:text-indigo-400 underline hover:no-underline">Annuler</button>

                    <form method="post" action="{{ route('analytic-axis.destroy', $analytic_axis) }}">
                        @method('delete')
                        @csrf

                        <x-danger-button>Archiver l'axe</x-danger-button>
                    </form>
                </x-slot:footer>
            </x-dialog>
        @endif

    </div>

</x-app-layout>
