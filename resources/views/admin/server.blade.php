{{--
    État du serveur, page de l'administrateur : les alertes en tête, puis une carte par section du
    relevé, avec à gauche la navigation entre les sections, qui porte l'état de chacune.

    @var string $taken_at Date et heure du relevé, en français.
    @var list<\App\Support\ServerStatus\StatusSection> $sections Sections du relevé.
    @var list<array{section: \App\Support\ServerStatus\StatusSection, item: \App\Support\ServerStatus\StatusItem}> $alerts
        Alertes, les anomalies d'abord.
    @var string $alert_type Type du résumé des alertes (<x-alert>) : success, warning ou danger.
    @var string $alert_title Titre du résumé des alertes (« Rien à signaler », « 2 points à surveiller »).
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-xl font-semibold">État du serveur</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Relevé du {{ $taken_at }}</p>
            </div>

            <x-link.secondary :target="route('admin.server')" class="flex items-center gap-2">
                <x-lucide-refresh-cw class="size-4" aria-hidden="true"/>
                Actualiser
            </x-link.secondary>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:items-start">
        <x-side-nav
            label="Sections du relevé"
            x-data="sectionSpy"
            class="sticky top-0 z-10 bg-gray-100 dark:bg-gray-900 lg:top-6 lg:bg-transparent lg:dark:bg-transparent"
        >
            <x-side-nav.item href="#alerts" :status="$alerts === [] ? 'ok' : $alerts[0]['item']->level" :count="count($alerts)">
                Alertes
            </x-side-nav.item>

            @foreach ($sections as $section)
                <x-side-nav.item :href="'#'.$section->id" :status="$section->level()">
                    {{ $section->title }}
                </x-side-nav.item>
            @endforeach
        </x-side-nav>

        <div class="flex min-w-0 flex-col gap-6">
            <section id="alerts" aria-labelledby="alerts-title" class="scroll-mt-16 lg:scroll-mt-6">
                <x-alert :type="$alert_type">
                    <div class="flex flex-col gap-1 whitespace-normal">
                        <h3 id="alerts-title" class="font-semibold">{{ $alert_title }}</h3>

                        @if ($alerts !== [])
                            <ul class="flex flex-col gap-1 list-disc pl-5">
                                @foreach ($alerts as $alert)
                                    <li>
                                        <a href="#{{ $alert['section']->id }}" class="underline underline-offset-2 hover:no-underline">{{ $alert['section']->title }}</a> :
                                        {{ $alert['item']->alert }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </x-alert>
            </section>

            @foreach ($sections as $section)
                <x-card :id="$section->id" class="scroll-mt-16 lg:scroll-mt-6">
                    <x-slot:title>{{ $section->title }}</x-slot:title>

                    <dl class="flex flex-col divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($section->items as $item)
                            <div class="grid gap-1 py-2.5 first:pt-0 last:pb-0 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-4">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $item->label }}</dt>

                                <dd class="flex min-w-0 flex-col gap-1.5 text-sm tabular-nums">
                                    <span
                                        @class([
                                            'flex', 'items-start', 'gap-1.5', 'break-words',
                                            'text-gray-900 dark:text-gray-100' => $item->level === 'ok',
                                            'font-medium text-amber-700 dark:text-amber-400' => $item->level === 'warning',
                                            'font-medium text-red-700 dark:text-red-400' => $item->level === 'danger',
                                        ])
                                    >
                                        @if ($item->level === 'warning')
                                            <x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true"/>
                                            <span class="sr-only">À surveiller :</span>
                                        @elseif ($item->level === 'danger')
                                            <x-lucide-circle-x class="mt-0.5 size-4 shrink-0" aria-hidden="true"/>
                                            <span class="sr-only">Anomalie :</span>
                                        @endif
                                        <span class="min-w-0">{{ $item->value }}</span>
                                    </span>

                                    @if ($item->meter !== null)
                                        <x-meter :value="$item->meter" :level="$item->level"/>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            @endforeach
        </div>
    </div>
</x-app-layout>
