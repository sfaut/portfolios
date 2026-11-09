{{--
    Mise en page invité (connexion, inscription, mot de passe, vérification, pages d'erreur) : le
    logotype (logo et nom côte à côte, lien vers l'accueil), puis le contenu dans une carte.

    @var \Illuminate\View\ComponentSlot $slot Contenu de la carte.
--}}
<x-empty-layout>

    <div @class([
        'min-h-screen',
        'flex', 'flex-col', 'gap-6',
        'items-center', 'justify-center',
        'bg-gray-100', 'dark:bg-gray-900',
    ])>

        <h1>
            <a
                href="/"
                @class([
                    'flex', 'items-center', 'gap-3',
                    'rounded-md', 'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500', 'focus-visible:ring-offset-4',
                    'focus-visible:ring-offset-gray-100', 'dark:focus-visible:ring-offset-gray-900',
                    'text-indigo-800', 'dark:text-indigo-400',
                ])
            >
                <x-application-logo class="size-11 shrink-0" aria-hidden="true"/>
                <span class="text-4xl leading-none font-medium">Portfolios</span>
            </a>
        </h1>

        <div @class([
            'max-w-md', 'w-full',
            'flex', 'flex-col', 'gap-4',
            'px-6', 'py-6',
            'bg-white', 'dark:bg-gray-800',
            'shadow-md', 'overflow-hidden',
            'rounded-lg',
        ])>
            {{ $slot }}
        </div>

    </div>

</x-empty-layout>
