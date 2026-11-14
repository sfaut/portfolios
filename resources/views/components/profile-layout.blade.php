@props([
    'title',
    'current',
])

{{--
    Mise en page des pages du profil : le bandeau au titre de la page, puis la navigation latérale
    entre les pages du profil et le contenu de la page courante.

    @var string $title Titre de la page, dans le bandeau.
    @var string $current Nom de la route de la page courante (profile.edit, profile.preferences…).
    @var \Illuminate\View\ComponentSlot $slot Contenu de la page : ses cartes.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">{{ $title }}</h2>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:items-start">
        <x-side-nav label="Pages du profil" class="sticky top-0 z-10 bg-gray-100 dark:bg-gray-900 lg:top-6 lg:bg-transparent lg:dark:bg-transparent">
            @foreach ([
                'profile.edit' => 'Profil',
                'profile.preferences' => 'Préférences',
                'profile.password' => 'Mot de passe',
                'profile.account' => 'Données et compte',
            ] as $route => $page)
                <x-side-nav.item :href="route($route)" :current="$route === $current">{{ $page }}</x-side-nav.item>
            @endforeach
        </x-side-nav>

        <div class="flex min-w-0 max-w-2xl flex-col gap-6">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
