{{--
    Téléchargement des données de l'utilisateur, en archive ZIP de fichiers CSV (DataExportController).
--}}
<x-card>
    <x-slot:title>Téléchargement des données</x-slot:title>
    <x-slot:description>
        Une archive ZIP de fichiers CSV, lisibles dans un tableur : votre profil, vos portfolios, comptes,
        opérations et axes analytiques, archivés compris.
    </x-slot:description>

    <x-slot:footer>
        <x-link.primary :target="route('profile.export')">Télécharger mes données</x-link.primary>
    </x-slot:footer>
</x-card>
