{{--
    Pastille d'un axe analytique (App\View\Components\AxisBadge) : icône et nom aux couleurs de
    l'axe, ou l'icône colorée seule (icon-only), masquée aux lecteurs d'écran, le nom étant alors
    écrit à côté par l'appelant.

    @var string $name Nom de l'axe.
    @var string $icon Composant de l'icône (lucide-…).
    @var \App\Enums\AxisColor $color Couleur de l'axe.
    @var bool $archived Si l'axe est archivé.
    @var bool $iconOnly Si la pastille se réduit à l'icône.
    @var bool $onDark Si l'icône seule se pose sur un fond toujours sombre.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la pastille.
--}}
@if ($iconOnly)
    <x-dynamic-component :component="$icon" :class="Arr::toCssClasses(['size-4', 'shrink-0', $onDark ? $color->onDarkClasses() : $color->textClasses(), $attributes->get('class')])" aria-hidden="true"/>
@else
    <span {{ $attributes->class(['inline-flex', 'items-center', 'gap-1', 'rounded-full', 'px-2', 'py-0.5', 'text-xs', 'font-semibold', 'whitespace-nowrap', $color->badgeClasses()]) }}>
        <x-dynamic-component :component="$icon" class="size-3.5 shrink-0" aria-hidden="true"/>
        <span>{{ $name }}</span>
        @if ($archived)
            <span class="font-normal opacity-75">(archivé)</span>
        @endif
    </span>
@endif
