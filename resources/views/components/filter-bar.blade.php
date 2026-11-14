{{--
    Barre de filtres d'une page (Grand livre, comptes, tableau de bord) : un formulaire en carte, ses
    champs (<x-filter-field>) alignés par le bas et repliés à la ligne, en petit texte (`text-sm`),
    que les contrôles héritent. À droite, l'état de la barre : chargement, erreur, résumé, réinitialisation,
    bouton d'envoi sans JavaScript.

    L'appelant donne les attributs du formulaire (id, method, action, aria-label, x-on:submit…).

    @var \Illuminate\View\ComponentSlot $slot Champs de la barre.
    @var \Illuminate\View\ComponentSlot|null $status État de la barre, à droite, omis s'il est absent.
--}}
<form
    role="search"
    {{ $attributes->class('flex flex-wrap items-end gap-x-4 gap-y-3 rounded-md bg-white dark:bg-gray-800 p-4 text-sm shadow-sm') }}
>
    {{ $slot }}

    @isset($status)
        <div class="flex grow items-center justify-end gap-4 self-end py-2">
            {{ $status }}
        </div>
    @endisset
</form>
