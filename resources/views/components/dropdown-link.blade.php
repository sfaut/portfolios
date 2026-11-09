@props([
    'icon' => null,
])

{{--
    Entrée d'un menu déroulant (<x-dropdown>), précédée d'une icône Lucide facultative, de la couleur
    du texte.

    <x-dropdown-link :href="route('profile.edit')" icon="circle-user-round">Profil</x-dropdown-link>

    @var string|null $icon Nom de l'icône Lucide (https://lucide.dev/icons), null sans icône.
    @var \Illuminate\View\ComponentSlot $slot Texte de l'entrée.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du lien (href…), ses classes ajoutées aux siennes.
--}}
<a
    {{
        $attributes->class([
            'block' => $icon === null,
            'flex items-center gap-2' => $icon !== null,
            'w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300',
            'hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-gray-800',
            'transition duration-150 ease-in-out',
        ])
    }}
>
    @if ($icon !== null)
        <x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>
    @endif
    {{ $slot }}
</a>
