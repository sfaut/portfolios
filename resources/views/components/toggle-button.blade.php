{{--
    Bouton poussoir : un bouton à deux états, relâché ou enfoncé (aria-pressed), qui envoie le
    formulaire où il se trouve avec l'état opposé à l'état courant, name=1 pour l'enfoncer, name=0
    pour le relâcher. L'état vient du serveur, qui le reçoit et réaffiche la page : le composant
    marche sans JavaScript, dans un formulaire GET pour une préférence d'affichage.

    Relâché, il a l'allure blanche des boutons de pagination ; enfoncé, il passe en indigo plein, avec
    une ombre intérieure qui le montre enfoncé. L'apparence suit l'attribut aria-pressed (variantes
    aria-pressed:), comme les boutons du thème dans le menu utilisateur.

    @var string $name Nom du paramètre envoyé.
    @var bool $pressed Si le bouton est enfoncé.
    @var \Illuminate\View\ComponentSlot $slot Libellé du bouton.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du bouton (aria-label…).
--}}
@props([
    'name',
    'pressed' => false,
])

<button
    type="submit"
    name="{{ $name }}"
    value="{{ $pressed ? '0' : '1' }}"
    aria-pressed="{{ $pressed ? 'true' : 'false' }}"
    {{ $attributes->class([
        'inline-flex', 'items-center', 'justify-center',
        'rounded-md', 'px-3', 'py-2',
        'text-xs', 'font-semibold', 'tracking-wide',
        'focus-visible:outline-2', 'focus-visible:outline-offset-2', 'focus-visible:outline-indigo-500',
        'active:translate-y-px',
        'transition-colors', 'duration-100', 'ease-linear', 'motion-reduce:transition-none',
        'bg-white', 'text-gray-600', 'shadow-sm', 'hover:bg-gray-50',
        'dark:bg-gray-800', 'dark:text-gray-300', 'dark:hover:bg-gray-700',
        'aria-pressed:bg-indigo-700', 'aria-pressed:text-white', 'aria-pressed:shadow-[inset_0_2px_4px_rgb(0_0_0/0.35)]',
        'aria-pressed:hover:bg-indigo-800',
        'dark:aria-pressed:bg-indigo-500', 'dark:aria-pressed:text-white', 'dark:aria-pressed:hover:bg-indigo-600',
    ]) }}
>{{ $slot }}</button>
