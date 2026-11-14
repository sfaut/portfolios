{{--
    Bouton, ou lien à l'allure de bouton (App\View\Components\Button) : un lien avec href, un bouton
    sinon, l'icône devant le texte.

    @var string|null $href Adresse du lien, null pour un bouton.
    @var string $type Type du bouton (button, submit, reset).
    @var string|null $icon Nom de l'icône Lucide placée devant le texte, null sans icône.
    @var list<string> $classes Classes de la variante.
    @var \Illuminate\View\ComponentSlot $slot Texte du bouton.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de l'appelant.
--}}
@if ($href !== null)
<a href="{{ $href }}" {{ $attributes->class($classes) }}>@if ($icon !== null)<x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>@endif{{ $slot }}</a>
@else
<button type="{{ $type }}" {{ $attributes->class($classes) }}>@if ($icon !== null)<x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>@endif{{ $slot }}</button>
@endif
