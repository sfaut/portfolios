{{--
    Case de pointage d'une opération : cochée, l'opération est pointée, au sens que l'utilisateur
    donne au pointage.

    Avec JavaScript, le composant Alpine `tickBox` (resources/js/tick-box.js) enregistre l'état
    aussitôt, sans quitter la page, et le confirme par un toast qui propose d'annuler. Sans
    JavaScript, le bouton « Enregistrer » envoie le petit formulaire (PATCH operation.tick), qui
    revient à la page avec le toast du serveur. Le champ caché envoie 0 quand la case est décochée.
    Désactivée (compte archivé), la case montre l'état seul.

    Avec JavaScript, l'étiquette de la case couvre la cellule de tableau qui la tient, posée en
    `relative` par l'appelant : un clic n'importe où dans la cellule pointe ou dépointe. Sans
    JavaScript, elle reste à sa place, à côté du bouton d'envoi.

    @var \App\Models\Operation|\App\Models\OperationReport $operation Opération pointée ou non.
    @var bool $disabled Si la case montre l'état sans le changer.
--}}
@props(['operation', 'disabled' => false])

@if ($disabled)
    <input
        type="checkbox"
        disabled
        @checked($operation->is_ticked)
        aria-label="Pointée : {{ $operation->label }}"
        class="size-4 rounded-sm border-gray-300 text-indigo-600 opacity-60 dark:border-gray-600 dark:bg-gray-900"
    >
@else
    <form
        method="post"
        action="{{ route('operation.tick', $operation->id) }}"
        x-data="tickBox(@js(['id' => $operation->id, 'label' => $operation->label, 'ticked' => (bool) $operation->is_ticked]))"
        x-on:submit.prevent
        class="inline-flex items-center gap-2"
    >
        @csrf
        @method('patch')
        <input type="hidden" name="is_ticked" value="0">
        <label
            x-bind:class="'absolute inset-0'"
            class="flex cursor-pointer items-center justify-center hover:bg-indigo-50 has-focus-visible:outline-2 has-focus-visible:-outline-offset-2 has-focus-visible:outline-indigo-500 has-aria-busy:cursor-progress dark:hover:bg-indigo-950/40"
        >
            <input
                type="checkbox"
                name="is_ticked"
                value="1"
                x-ref="box"
                x-on:change="send($event.target.checked)"
                x-bind:aria-busy="busy"
                @checked($operation->is_ticked)
                aria-label="Pointée : {{ $operation->label }}"
                class="size-4 cursor-pointer rounded-sm border-gray-300 text-indigo-600 focus:ring-0 focus:ring-offset-0 aria-busy:cursor-progress dark:border-gray-600 dark:bg-gray-900"
            >
        </label>
        <noscript>
            <button type="submit" class="text-xs font-semibold text-indigo-700 uppercase hover:underline dark:text-indigo-400">Enregistrer</button>
        </noscript>
    </form>
@endif
