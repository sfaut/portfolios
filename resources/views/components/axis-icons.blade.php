{{--
    Icônes des axes analytiques d'un compte, à côté de son nom : l'icône colorée de chaque axe, dont
    une infobulle rappelle le nom au survol (« (archivé) » au besoin). Le nom est aussi écrit pour les
    lecteurs d'écran. Rien sans axe.

    Hors d'un lien, focusable rend chaque icône atteignable au clavier, qui ouvre alors l'infobulle ;
    dans un lien (carte d'un compte), le lien garde seul le focus.

    @var iterable<\App\Models\AnalyticAxis> $axes Axes à montrer, dans leur ordre.
    @var bool $focusable Si chaque icône prend le focus.
    @var string $iconClass Classes ajoutées aux icônes (couleur au survol de la carte…).
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du groupe.
--}}
@props([
    'axes' => [],
    'focusable' => false,
    'iconClass' => '',
])

@if (count($axes) > 0)
    <span {{ $attributes->class(['inline-flex', 'items-center', 'gap-1', 'align-middle']) }}>
        @foreach ($axes as $axis)
            <x-tooltip>
                <span class="flex" @if ($focusable) tabindex="0" @endif>
                    <x-axis-badge :axis="$axis" icon-only :class="$iconClass"/>
                    <span class="sr-only">{{ $axis->name }}@if ($axis->trashed()) (archivé)@endif</span>
                </span>

                <x-slot:content>
                    {{ $axis->name }}@if ($axis->trashed()) (archivé)@endif
                </x-slot:content>
            </x-tooltip>
        @endforeach
    </span>
@endif
