<?php

namespace App\Support;

/**
 * Icônes Lucide proposées pour un axe analytique, groupées par thème.
 *
 * Chaque icône porte un nom en français, affiché au survol et lu par les lecteurs d'écran, et des
 * mots-clefs en français pour la recherche, qui cherche aussi dans le nom Lucide (anglais). Les
 * familles musique, jeux, sport et arts sont complètes : toute icône Lucide de ces familles y
 * figure. Une icône s'ajoute dans son thème, sous son nom Lucide (https://lucide.dev/icons) ;
 * AxisIconsTest vérifie qu'elle existe dans le paquet installé.
 */
final class AxisIcons
{
    /**
     * Icône d'un axe qui n'en a pas choisi.
     */
    public const string DEFAULT = 'tag';

    /**
     * Thèmes, dans l'ordre d'affichage : nom Lucide => [nom, mots-clefs].
     *
     * @var array<string, array<string, array{0: string, 1: string}>>
     */
    public const array THEMES = [
        'Musique' => [
            'piano' => ['Piano', 'clavier cours instrument'],
            'keyboard-music' => ['Clavier', 'synthétiseur piano orgue instrument'],
            'guitar' => ['Guitare', 'cordes instrument'],
            'guitar-electric' => ['Guitare électrique', 'cordes rock instrument'],
            'drum' => ['Tambour', 'batterie percussion instrument'],
            'drumstick' => ['Baguette', 'batterie percussion pilon poulet'],
            'mic-vocal' => ['Micro', 'chant voix chorale karaoké'],
            'music' => ['Note', 'musique solfège'],
            'music-2' => ['Croche', 'musique note'],
            'music-3' => ['Noire', 'musique note'],
            'music-4' => ['Doubles croches', 'musique notes'],
            'list-music' => ['Liste de lecture', 'playlist musique morceaux'],
            'file-music' => ['Partition', 'musique fichier morceau'],
            'audio-lines' => ['Son', 'audio onde enregistrement'],
            'headphones' => ['Casque', 'écoute audio musique'],
            'speaker' => ['Enceinte', 'haut-parleur sono'],
            'volume-2' => ['Volume', 'son haut-parleur'],
            'radio' => ['Radio', 'poste émission'],
            'disc-3' => ['Disque', 'cd vinyle album'],
            'disc-album' => ['Album', 'disque vinyle'],
        ],
        'Jeux' => [
            'chess-king' => ['Roi', 'échecs pièce jeu'],
            'chess-queen' => ['Dame', 'échecs reine pièce jeu'],
            'chess-rook' => ['Tour', 'échecs pièce jeu'],
            'chess-bishop' => ['Fou', 'échecs pièce jeu'],
            'chess-knight' => ['Cavalier', 'échecs pièce jeu'],
            'chess-pawn' => ['Pion', 'échecs pièce jeu'],
            'dices' => ['Dés', 'jeu société hasard'],
            'dice-1' => ['Dé 1', 'jeu hasard'],
            'dice-2' => ['Dé 2', 'jeu hasard'],
            'dice-3' => ['Dé 3', 'jeu hasard'],
            'dice-4' => ['Dé 4', 'jeu hasard'],
            'dice-5' => ['Dé 5', 'jeu hasard'],
            'dice-6' => ['Dé 6', 'jeu hasard'],
            'spade' => ['Pique', 'cartes jeu'],
            'heart' => ['Cœur', 'cartes jeu amour'],
            'diamond' => ['Carreau', 'cartes jeu losange'],
            'club' => ['Trèfle', 'cartes jeu'],
            'puzzle' => ['Puzzle', 'casse-tête jeu pièce'],
            'toy-brick' => ['Brique', 'jouet construction jeu'],
            'gamepad' => ['Manette', 'jeu vidéo console'],
            'gamepad-2' => ['Manette 2', 'jeu vidéo console'],
            'gamepad-directional' => ['Croix directionnelle', 'jeu vidéo manette'],
            'joystick' => ['Joystick', 'jeu vidéo arcade'],
            'swords' => ['Épées', 'jeu rôle combat escrime'],
            'crown' => ['Couronne', 'roi jeu victoire'],
            'castle' => ['Château', 'jeu fort donjon'],
            'balloon' => ['Ballon de baudruche', 'fête anniversaire'],
            'party-popper' => ['Fête', 'cotillons anniversaire célébration'],
        ],
        'Sport' => [
            'dumbbell' => ['Haltère', 'musculation salle sport'],
            'volleyball' => ['Volley', 'ballon sport'],
            'rugby-ball' => ['Rugby', 'ballon sport'],
            'bike' => ['Vélo', 'cyclisme bicyclette sport'],
            'footprints' => ['Pas', 'marche course randonnée'],
            'waves-ladder' => ['Piscine', 'natation nage'],
            'mountain-snow' => ['Montagne', 'ski neige alpinisme'],
            'sailboat' => ['Voilier', 'voile bateau nautisme'],
            'fishing-rod' => ['Canne à pêche', 'pêche loisir'],
            'fishing-hook' => ['Hameçon', 'pêche'],
            'target' => ['Cible', 'tir objectif fléchettes'],
            'goal' => ['But', 'objectif drapeau'],
            'trophy' => ['Trophée', 'coupe victoire compétition'],
            'medal' => ['Médaille', 'récompense compétition'],
            'award' => ['Récompense', 'prix distinction'],
        ],
        'Arts et sorties' => [
            'palette' => ['Palette', 'peinture dessin art'],
            'paintbrush' => ['Pinceau', 'peinture art'],
            'brush' => ['Brosse', 'peinture art'],
            'pen-tool' => ['Plume', 'dessin graphisme'],
            'camera' => ['Appareil photo', 'photographie'],
            'clapperboard' => ['Clap', 'cinéma tournage vidéo'],
            'film' => ['Film', 'cinéma vidéo pellicule'],
            'popcorn' => ['Popcorn', 'cinéma sortie'],
            'drama' => ['Masques', 'théâtre comédie spectacle'],
            'theater' => ['Théâtre', 'scène spectacle'],
            'ticket' => ['Billet', 'entrée spectacle concert'],
            'binoculars' => ['Jumelles', 'observation nature'],
            'tent' => ['Tente', 'camping randonnée'],
            'caravan' => ['Caravane', 'camping vacances'],
        ],
        'Argent' => [
            'tag' => ['Étiquette', 'catégorie prix'],
            'tags' => ['Étiquettes', 'catégories prix'],
            'wallet' => ['Portefeuille', 'argent dépenses'],
            'piggy-bank' => ['Tirelire', 'épargne économies'],
            'banknote' => ['Billet de banque', 'argent espèces'],
            'coins' => ['Pièces', 'argent monnaie'],
            'euro' => ['Euro', 'argent devise'],
            'badge-euro' => ['Prix', 'euro tarif'],
            'credit-card' => ['Carte bancaire', 'paiement banque'],
            'hand-coins' => ['Paiement', 'argent don pourboire'],
            'receipt-euro' => ['Reçu', 'facture ticket caisse'],
            'calculator' => ['Calculatrice', 'comptabilité calcul'],
            'landmark' => ['Banque', 'institution administration impôts'],
            'vault' => ['Coffre-fort', 'épargne sécurité'],
            'percent' => ['Pourcentage', 'remise taux intérêt'],
            'chart-pie' => ['Camembert', 'graphique répartition'],
            'trending-up' => ['Hausse', 'croissance revenu'],
            'trending-down' => ['Baisse', 'perte dépense'],
            'scale' => ['Balance', 'justice équilibre juridique'],
        ],
        'Travail et commerce' => [
            'briefcase' => ['Mallette', 'travail emploi bureau'],
            'building-2' => ['Immeuble', 'bureau entreprise société'],
            'factory' => ['Usine', 'industrie production'],
            'store' => ['Boutique', 'magasin commerce'],
            'shopping-cart' => ['Caddie', 'courses supermarché achats'],
            'shopping-basket' => ['Panier', 'courses marché achats'],
            'shopping-bag' => ['Sac', 'achats shopping'],
            'package' => ['Colis', 'livraison carton'],
            'truck' => ['Camion', 'livraison transport'],
            'handshake' => ['Poignée de main', 'contrat partenariat client'],
            'clipboard-list' => ['Liste', 'tâches checklist'],
            'file-text' => ['Document', 'fichier contrat'],
            'folder' => ['Dossier', 'classement fichiers'],
            'archive' => ['Archives', 'carton classement'],
            'newspaper' => ['Journal', 'presse abonnement'],
            'mail' => ['Courrier', 'lettre poste e-mail'],
            'phone' => ['Téléphone', 'appel forfait'],
            'calendar' => ['Calendrier', 'agenda date'],
            'clock' => ['Horloge', 'heure temps'],
            'hourglass' => ['Sablier', 'temps attente'],
            'hammer' => ['Marteau', 'bricolage travaux'],
            'wrench' => ['Clef', 'réparation entretien mécanique'],
            'scissors' => ['Ciseaux', 'coiffeur couture'],
            'hard-hat' => ['Casque de chantier', 'travaux bâtiment'],
            'construction' => ['Travaux', 'chantier barrière'],
            'shovel' => ['Pelle', 'jardinage terrassement'],
        ],
        'Maison' => [
            'house' => ['Maison', 'logement domicile loyer'],
            'key-round' => ['Clef', 'location logement serrure'],
            'sofa' => ['Canapé', 'meuble salon'],
            'bed' => ['Lit', 'chambre hébergement nuit'],
            'lamp' => ['Lampe', 'éclairage meuble'],
            'plug' => ['Prise', 'électricité branchement'],
            'zap' => ['Éclair', 'électricité énergie'],
            'flame' => ['Flamme', 'gaz chauffage feu'],
            'droplets' => ['Gouttes', 'eau facture'],
            'shower-head' => ['Douche', 'salle de bain eau'],
            'bath' => ['Baignoire', 'salle de bain'],
            'washing-machine' => ['Lave-linge', 'électroménager lessive'],
            'refrigerator' => ['Réfrigérateur', 'électroménager frigo'],
            'microwave' => ['Micro-ondes', 'électroménager cuisine'],
            'paint-roller' => ['Rouleau', 'peinture décoration travaux'],
            'trash-2' => ['Poubelle', 'déchets ordures'],
            'recycle' => ['Recyclage', 'tri déchets'],
            'shield' => ['Bouclier', 'assurance protection sécurité'],
            'lock' => ['Cadenas', 'sécurité alarme'],
        ],
        'Alimentation' => [
            'utensils' => ['Couverts', 'repas restaurant'],
            'chef-hat' => ['Toque', 'cuisine chef restaurant'],
            'cooking-pot' => ['Marmite', 'cuisine repas'],
            'coffee' => ['Café', 'boisson pause'],
            'cup-soda' => ['Soda', 'boisson'],
            'glass-water' => ['Verre d\'eau', 'boisson'],
            'wine' => ['Vin', 'alcool boisson cave'],
            'beer' => ['Bière', 'alcool boisson bar'],
            'pizza' => ['Pizza', 'repas restaurant'],
            'hamburger' => ['Hamburger', 'restauration rapide'],
            'sandwich' => ['Sandwich', 'repas déjeuner'],
            'soup' => ['Soupe', 'repas'],
            'salad' => ['Salade', 'repas légumes'],
            'croissant' => ['Croissant', 'boulangerie petit-déjeuner'],
            'cake' => ['Gâteau', 'anniversaire pâtisserie'],
            'ice-cream-cone' => ['Glace', 'dessert'],
            'candy' => ['Bonbon', 'confiserie'],
            'cookie' => ['Biscuit', 'goûter'],
            'apple' => ['Pomme', 'fruit'],
            'cherry' => ['Cerises', 'fruit'],
            'citrus' => ['Agrume', 'citron orange fruit'],
            'grape' => ['Raisin', 'fruit vigne'],
            'banana' => ['Banane', 'fruit'],
            'carrot' => ['Carotte', 'légume'],
            'wheat' => ['Blé', 'céréales pain agriculture'],
            'egg' => ['Œuf', 'cuisine'],
            'fish' => ['Poisson', 'poissonnerie pêche'],
            'beef' => ['Viande', 'boucherie'],
            'milk' => ['Lait', 'crèmerie'],
        ],
        'Santé' => [
            'heart-pulse' => ['Santé', 'cœur médecine'],
            'stethoscope' => ['Stéthoscope', 'médecin consultation'],
            'pill' => ['Médicament', 'pharmacie'],
            'syringe' => ['Seringue', 'vaccin piqûre'],
            'hospital' => ['Hôpital', 'clinique soins'],
            'ambulance' => ['Ambulance', 'urgence'],
            'bone' => ['Os', 'radiographie kiné'],
            'brain' => ['Cerveau', 'psychologie réflexion'],
            'eye' => ['Œil', 'ophtalmologue lunettes vue'],
            'glasses' => ['Lunettes', 'opticien vue'],
            'ear' => ['Oreille', 'audition'],
            'dna' => ['ADN', 'génétique laboratoire'],
            'accessibility' => ['Accessibilité', 'handicap'],
            'toothbrush' => ['Brosse à dents', 'dentiste hygiène'],
        ],
        'Éducation' => [
            'graduation-cap' => ['Diplôme', 'études université'],
            'school' => ['École', 'scolarité'],
            'book-open' => ['Livre', 'lecture cours'],
            'library' => ['Bibliothèque', 'livres'],
            'notebook-pen' => ['Cahier', 'notes cours'],
            'pencil' => ['Crayon', 'écriture fournitures'],
            'pencil-ruler' => ['Règle', 'fournitures dessin'],
            'backpack' => ['Sac à dos', 'école cartable'],
            'microscope' => ['Microscope', 'science laboratoire'],
            'flask-conical' => ['Fiole', 'chimie science'],
            'atom' => ['Atome', 'physique science'],
            'languages' => ['Langues', 'traduction cours'],
            'lightbulb' => ['Ampoule', 'idée projet'],
        ],
        'Personnes et animaux' => [
            'user' => ['Personne', 'client individu'],
            'users' => ['Groupe', 'personnes équipe famille'],
            'baby' => ['Bébé', 'enfant naissance crèche'],
            'hand-heart' => ['Don', 'solidarité association'],
            'hand-helping' => ['Aide', 'service entraide'],
            'church' => ['Église', 'culte religion'],
            'gift' => ['Cadeau', 'présent anniversaire'],
            'dog' => ['Chien', 'animal vétérinaire'],
            'cat' => ['Chat', 'animal vétérinaire'],
            'paw-print' => ['Patte', 'animal'],
            'bird' => ['Oiseau', 'animal'],
            'rabbit' => ['Lapin', 'animal'],
            'squirrel' => ['Écureuil', 'animal'],
            'turtle' => ['Tortue', 'animal'],
            'snail' => ['Escargot', 'animal'],
            'bug' => ['Insecte', 'animal'],
        ],
        'Transport et voyage' => [
            'car' => ['Voiture', 'auto automobile'],
            'fuel' => ['Carburant', 'essence station'],
            'parking-meter' => ['Parcmètre', 'stationnement parking'],
            'bus' => ['Bus', 'transport en commun'],
            'train-front' => ['Train', 'rail transport'],
            'tram-front' => ['Tramway', 'transport en commun'],
            'motorbike' => ['Moto', 'deux-roues'],
            'plane' => ['Avion', 'vol voyage'],
            'ship' => ['Bateau', 'ferry croisière'],
            'rocket' => ['Fusée', 'lancement projet'],
            'luggage' => ['Valise', 'voyage bagage'],
            'hotel' => ['Hôtel', 'hébergement voyage'],
            'tickets-plane' => ['Billet d\'avion', 'voyage réservation'],
            'map-pin' => ['Lieu', 'adresse localisation'],
            'globe' => ['Globe', 'monde international voyage'],
            'anchor' => ['Ancre', 'port marine'],
        ],
        'Technologie' => [
            'smartphone' => ['Smartphone', 'mobile téléphone'],
            'laptop' => ['Ordinateur portable', 'informatique'],
            'monitor' => ['Écran', 'ordinateur informatique'],
            'tv' => ['Télévision', 'abonnement écran'],
            'wifi' => ['Wi-Fi', 'internet abonnement'],
            'server' => ['Serveur', 'hébergement informatique'],
            'printer' => ['Imprimante', 'impression'],
        ],
        'Nature' => [
            'leaf' => ['Feuille', 'nature écologie'],
            'sprout' => ['Pousse', 'jardin plante'],
            'flower-2' => ['Fleur', 'jardin fleuriste'],
            'trees' => ['Arbres', 'forêt nature'],
            'tree-palm' => ['Palmier', 'vacances plage'],
            'mountain' => ['Montagne', 'nature randonnée'],
            'waves' => ['Vagues', 'mer plage'],
            'sun' => ['Soleil', 'été beau temps'],
            'cloud-rain' => ['Pluie', 'météo'],
            'umbrella' => ['Parapluie', 'pluie'],
        ],
        'Divers' => [
            'star' => ['Étoile', 'favori important'],
            'sparkles' => ['Étincelles', 'nouveau magie'],
            'gem' => ['Gemme', 'bijou luxe précieux'],
            'shirt' => ['Vêtement', 'habillement chemise'],
            'flag' => ['Drapeau', 'objectif repère'],
            'key' => ['Clef ancienne', 'accès'],
            'circle-dashed' => ['Cercle pointillé', 'autre indéfini'],
        ],
    ];

    /**
     * Noms Lucide de toutes les icônes proposées : la liste blanche du formulaire.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_merge(...array_map(array_keys(...), array_values(self::THEMES)));
    }

    /**
     * Nom français d'une icône proposée, son nom Lucide sinon.
     */
    public static function label(string $icon): string
    {
        foreach (self::THEMES as $icons) {
            if (isset($icons[$icon])) {
                return $icons[$icon][0];
            }
        }

        return $icon;
    }
}
