{{--
    Apparence d'un axe analytique (App\View\Components\AxisAppearance), dans son formulaire : aperçu
    de la pastille, couleur, puis icône cherchée ou choisie par thème.

    Couleurs et icônes sont des radios natifs (champs color et icon) : sans JavaScript, le choix
    s'envoie tel quel, sans aperçu, recherche ni teinte. Avec le composant Alpine axisAppearance
    (resources/js/axis-appearance.js), l'aperçu suit le nom saisi, la grille prend la couleur
    choisie, la recherche filtre les icônes (sans casse ni accents) et les onglets mènent aux
    thèmes. L'icône choisie a le fond et l'anneau de la couleur ; sous la grille, une ligne nomme
    l'icône survolée, ou à défaut celle choisie.

    @var string $icon Icône choisie (nom Lucide).
    @var \App\Enums\AxisColor $color Couleur choisie.
    @var string $name Nom initial de l'axe.
    @var list<array{title: string, id: string, icons: list<array{name: string, label: string, search: string}>}> $themes Thèmes et icônes.
    @var array<string, mixed> $config Données du composant Alpine.
--}}
<div x-data="axisAppearance(@js($config))" class="flex flex-col gap-5">
    <div class="flex items-center gap-3" style="display: none" x-show="true">
        <span class="w-16 shrink-0 text-sm font-medium text-gray-700 dark:text-gray-300">Aperçu</span>
        <span
            class="inline-flex min-w-0 items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold [&_svg]:size-4 [&_svg]:shrink-0"
            x-bind:class="badges[color]"
        >
            <span class="flex" x-html="iconSvg" aria-hidden="true"></span>
            <span class="truncate" x-text="name.trim() || 'Nom de l\'axe'">{{ $name }}</span>
        </span>
    </div>

    <fieldset class="flex min-w-0 flex-col gap-2">
        <legend class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Couleur</legend>
        <div class="flex flex-wrap gap-2.5">
            @foreach (\App\Enums\AxisColor::cases() as $case)
                <label class="flex size-7 cursor-pointer items-center justify-center rounded-full ring-offset-2 ring-offset-white has-checked:ring-2 has-checked:ring-gray-900 has-focus-visible:outline-2 has-focus-visible:outline-offset-4 has-focus-visible:outline-indigo-500 dark:ring-offset-gray-800 dark:has-checked:ring-gray-100">
                    <input type="radio" name="color" value="{{ $case->value }}" @checked($case === $color) x-model="color" class="sr-only">
                    <span @class(['size-6', 'rounded-full', $case->barClasses()]) aria-hidden="true"></span>
                    <span class="sr-only">{{ $case->label() }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="flex min-w-0 flex-col gap-2">
        <legend class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Icône</legend>

        <div class="flex flex-col gap-2" style="display: none" x-show="true">
            <x-text-input
                type="search"
                x-model.debounce.150ms="query"
                x-on:keydown.enter.prevent
                placeholder="Chercher : piano, loyer, voiture…"
                aria-label="Chercher une icône"
                aria-controls="axis-icon-grid"
                class="w-full text-sm"
            />

            <nav aria-label="Thèmes d'icônes" x-show="query === ''" class="flex gap-1 overflow-x-auto pb-1">
                @foreach ($themes as $theme)
                    <button
                        type="button"
                        x-on:click="jump(@js($theme['id']))"
                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-indigo-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-100"
                    >{{ $theme['title'] }}</button>
                @endforeach
            </nav>
        </div>

        <div
            id="axis-icon-grid"
            x-ref="grid"
            x-bind:class="texts[color]"
            class="relative h-72 overflow-y-auto overscroll-contain rounded-md border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900"
        >
            @foreach ($themes as $theme)
                <section id="{{ $theme['id'] }}" data-theme aria-label="{{ $theme['title'] }}">
                    <h4 class="sticky top-0 z-10 bg-white px-3 py-1.5 text-xs font-medium text-gray-500 dark:bg-gray-900 dark:text-gray-400">{{ $theme['title'] }}</h4>
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(2.5rem,1fr))] gap-1 px-2 pt-1 pb-2">
                        @foreach ($theme['icons'] as $item)
                            <label
                                data-icon="{{ $item['name'] }}"
                                data-label="{{ $item['label'] }}"
                                data-search="{{ $item['search'] }}"
                                x-on:mouseenter="hint = $el.dataset.label"
                                x-on:mouseleave="hint = ''"
                                class="flex size-10 cursor-pointer items-center justify-center rounded-md hover:bg-gray-100 has-checked:bg-current/15 has-checked:ring-2 has-checked:ring-current has-focus-visible:outline-2 has-focus-visible:outline-indigo-500 dark:hover:bg-gray-800"
                            >
                                <input type="radio" name="icon" value="{{ $item['name'] }}" @checked($item['name'] === $icon) x-model="icon" class="sr-only">
                                <x-dynamic-component :component="'lucide-'.$item['name']" class="size-5" aria-hidden="true"/>
                                <span class="sr-only">{{ $item['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <p x-show="empty" style="display: none" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Aucune icône pour « <span x-text="query.trim()"></span> ». Essayez un autre mot : musique, sport, maison, voiture…
            </p>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400" style="display: none" x-show="true">
            <span x-text="hint ? hint : `Icône choisie : ${label}`"></span>
        </p>
    </fieldset>
</div>
