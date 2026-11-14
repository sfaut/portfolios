{{--
    Mise en page invité (connexion, inscription, mot de passe, vérification, pages d'erreur) : un écran
    partagé. À gauche, un panneau indigo foncé, en bandeau au-dessus du contenu sur un écran étroit,
    porte le logotype (lien vers l'accueil), l'accroche et la ligne en paliers du logo, animée en
    boucle (x-balance-steps). À droite, le titre de la page, sa description, son contenu, puis ses
    liens secondaires sous un filet.

    @var \Illuminate\View\ComponentSlot $title Titre de la page, dans le h1 et dans l'onglet.
    @var \Illuminate\View\ComponentSlot|null $description Phrase sous le titre, omise si absente.
    @var \Illuminate\View\ComponentSlot $slot Contenu de la page (formulaire, message…).
    @var \Illuminate\View\ComponentSlot|null $footer Liens secondaires, omis si absents.
--}}
<x-empty-layout :title="$title">

    <div class="min-h-dvh grid lg:grid-cols-[minmax(0,5fr)_minmax(0,4fr)] bg-white dark:bg-gray-900">

        <aside class="relative isolate overflow-hidden min-h-56 flex flex-col gap-8 lg:gap-12 p-8 lg:p-14 bg-indigo-950 text-white">
            <a
                href="/"
                class="self-start flex items-center gap-3 rounded-md focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-4 focus-visible:ring-offset-indigo-950"
            >
                <x-application-logo class="size-10 shrink-0" aria-hidden="true"/>
                <span class="text-3xl leading-none font-medium">Portfolios</span>
            </a>

            <p class="max-w-md text-2xl lg:text-4xl leading-tight font-extralight text-balance">
                Vos portefeuilles, vos comptes et leur solde au jour près.
            </p>

            <x-balance-steps class="absolute inset-x-0 bottom-0 -z-10 w-full max-h-full aspect-640/520"/>
        </aside>

        <main class="flex items-center justify-center px-6 py-10 sm:px-10 lg:py-14">
            <div class="w-full max-w-sm flex flex-col gap-8">

                <header class="flex flex-col gap-2">
                    <h1 class="text-3xl lg:text-4xl leading-tight font-semibold text-balance text-gray-900 dark:text-gray-100">{{ $title }}</h1>
                    @isset($description)
                        <p class="text-balance text-gray-600 dark:text-gray-400">{{ $description }}</p>
                    @endisset
                </header>

                {{ $slot }}

                @isset($footer)
                    <footer class="border-t border-gray-200 dark:border-gray-700 pt-6 text-sm text-gray-600 dark:text-gray-400">
                        {{ $footer }}
                    </footer>
                @endisset

            </div>
        </main>

    </div>

</x-empty-layout>
