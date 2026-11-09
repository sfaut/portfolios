@props([
    'links',
])

{{--
    Fil d'Ariane d'une page, sous son titre : les pages qui mènent à elle dans la section du portfolio
    (« Comptes › Compte courant »), la page elle-même exclue, puisque le titre la nomme. Les liens sont
    séparés par un chevron, masqué aux lecteurs d'écran, qui annoncent une liste.

    <x-breadcrumb :links="[route('portfolio.show', $portfolio) => 'Comptes', route('account.show', $account) => $account->name]"/>

    @var array<string, string> $links Liens, de la section à la page parente : adresse => texte.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la racine.
--}}
<nav aria-label="Fil d'Ariane" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
        @foreach ($links as $url => $text)
            <li class="flex min-w-0 items-center gap-1">
                @unless ($loop->first)
                    <x-lucide-chevron-right class="size-3.5 shrink-0" aria-hidden="true"/>
                @endunless
                <a href="{{ $url }}" class="truncate text-indigo-700 dark:text-indigo-400 underline hover:no-underline">{{ $text }}</a>
            </li>
        @endforeach
    </ol>
</nav>
