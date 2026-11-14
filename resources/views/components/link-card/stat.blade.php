{{--
    Mesure en bas d'une carte (<x-link-card>) : une icône et un court texte, en petit gris.

    @var string $icon Nom de l'icône Lucide (https://lucide.dev/icons).
    @var \Illuminate\View\ComponentSlot $slot Texte de la mesure (« 12 opérations »).
--}}
@props([
    'icon',
])

<span class="inline-flex items-center gap-1.5">
    <x-dynamic-component :component="'lucide-'.$icon" class="size-3.5 shrink-0" aria-hidden="true"/>
    {{ $slot }}
</span>
