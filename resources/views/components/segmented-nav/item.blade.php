@props([
    'href',
    'current' => false,
])

{{--
    Lien d'un contrôle segmenté (<x-segmented-nav>). La page courante passe en relief (fond blanc,
    ombre, texte indigo) et porte aria-current="page".

    @var string $href Adresse de la page.
    @var bool $current Si le lien mène à la page affichée.
    @var \Illuminate\View\ComponentSlot $slot Texte du lien.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du lien.
--}}
<a
    href="{{ $href }}"
    @if ($current) aria-current="page" @endif
    {{
        $attributes->class([
            'rounded', 'px-3', 'py-1.5', 'text-sm', 'font-medium', 'whitespace-nowrap',
            'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500',
            'bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 shadow-sm' => $current,
            'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' => ! $current,
        ])
    }}
>{{ $slot }}</a>
