{{--
    Message d'alerte, précédé de l'icône de son type, aux couleurs de ce type (App\View\Components\Alert).

    L'icône s'aligne sur la première ligne du message. Les retours à la ligne du message sont gardés
    (whitespace-pre-line), ses blancs de début et de fin retirés.

    @var list<string> $style Classes de couleur du type.
    @var string $icon Composant de l'icône du type (lucide-…).
    @var string $role Rôle ARIA : alert pour un danger, status sinon.
    @var \Illuminate\View\ComponentSlot $slot Message.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du bloc.
--}}
<div
    role="{{ $role }}"
    {{ $attributes->class(['flex', 'items-start', 'gap-4', 'rounded-md', 'px-5', 'py-3', 'shadow-md', 'ring-1', ...$style]) }}
>
    <span class="flex h-6 shrink-0 items-center">
        <x-dynamic-component :component="$icon" class="size-5" aria-hidden="true"/>
    </span>
    <div class="leading-6 whitespace-pre-line">{{ str($slot)->trim()->toHtmlString() }}</div>
</div>
