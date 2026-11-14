{{--
    Carte pointillée de création, en fin de liste de cartes (portfolios, comptes, axes) : une icône
    « plus » et le libellé de l'action, en indigo au survol.

    @var string $href Adresse du formulaire de création.
    @var \Illuminate\View\ComponentSlot $slot Libellé de l'action (« Créer un compte »).
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du lien.
--}}
@props([
    'href',
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'flex', 'flex-col', 'items-center', 'justify-center', 'gap-1',
        'rounded-md', 'border-2', 'border-dashed', 'border-gray-400', 'dark:border-gray-600',
        'p-4', 'text-center', 'text-gray-500', 'dark:text-gray-400',
        'hover:border-indigo-500', 'hover:text-indigo-700', 'dark:hover:border-indigo-400', 'dark:hover:text-indigo-400',
        'focus-visible:outline-2', 'focus-visible:outline-offset-2', 'focus-visible:outline-indigo-500',
        'transition', 'duration-150', 'ease-linear',
    ]) }}
>
    <x-lucide-circle-plus class="size-7.5 stroke-[1.25]" aria-hidden="true"/>
    <span>{{ $slot }}</span>
</a>
