# Changelog

Versions de Portfolios, de la plus récente à la plus ancienne.

## v1.5.1 (2026-11-09)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.5.1, qui affine le graphe des ventes par mois du tableau
de bord.

### ✨ Nouvelles fonctionnalités

- Les barres des ventes par mois portent un léger dégradé vertical, du plus clair en bas au plus foncé
  en haut, à l'échelle du graphe : le haut d'une barre fonce d'autant plus qu'elle monte, et toutes
  les barres se lisent sur la même échelle.

## v1.5.0 (2026-11-09)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.5.0, qui rend le tableau de bord plus lisible :

- un graphe des ventes par mois, comparé à l'année précédente, avec le cumul de la période ;
- les hausses et les baisses signalées d'un coup d'œil ;
- un sélecteur de période plus clair.

### ✨ Nouvelles fonctionnalités

- Graphe principal du tableau de bord : les ventes de chaque mois en barres, dont la teinte fonce avec
  le montant. Une barre grise plus fine donne le même mois un an plus tôt, et une courbe le cumul des
  ventes de la période, sur un second axe. Les axes sont gradués, et chaque mois a son infobulle.
  « Voir les chiffres » ajoute une colonne « Un an plus tôt ».
- Tendances : les comparaisons à l'année précédente portent une icône et une couleur, vert en hausse,
  rouge en baisse, gris si stable. Les lecteurs d'écran annoncent l'évolution en toutes lettres.
- Le sélecteur de période passe dans le bandeau du tableau de bord, puisqu'il vaut pour toute la page.
  Les chiffres clefs restent calculés au jour, avec leur date en légende.
- Le choix d'une période se fait en deux panneaux, « Début » et « Fin », chacun avec son année. Le
  champ affiche toute la période choisie.
- Période par défaut : les 14 derniers mois, du même mois un an plus tôt moins un mois jusqu'au mois
  en cours.

### 🗑️ Fonctionnalités retirées

- La courbe des ventes cumulées, comparée à l'année précédente, quitte le tableau de bord : le cumul
  est désormais dans le graphe des ventes par mois.

## v1.4.0 (2026-11-09)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.4.0, qui donne à chaque portfolio son tableau de bord et une
navigation stable, et renforce la sécurité des comptes :

- un tableau de bord des ventes pour chaque portfolio ;
- une navigation entre les pages d'un portfolio, toujours à la même place ;
- un nouveau logo ;
- une inscription et une connexion plus sûres, entièrement en français ;
- l'export de ses données et la suppression de son compte.

### ✨ Nouvelles fonctionnalités

- Tableau de bord d'un portfolio, devenu sa page d'accueil. Les montants négatifs y comptent comme des
  ventes. En tête, les chiffres clefs du jour : comptes actifs, ventes du mois en cours, du mois
  précédent et des 12 derniers mois, chacune comparée à un an plus tôt, et reste à encaisser. Puis les
  ventes d'une période au choix : total, courbe des ventes cumulées comparée à l'année précédente,
  ventes par mois et répartition par axe analytique.
- Navigation d'un portfolio : sur toutes ses pages, la barre du haut porte un sélecteur de portfolio
  (qui permet aussi de modifier ou de créer un portfolio) et les sections Tableau de bord, Comptes et
  Axes analytiques. Le titre de chaque page nomme la page, avec un fil d'Ariane court
  (« Comptes › Compte courant »). Le lien « Portfolios » de la barre disparaît : le logo mène à la
  liste.
- Nouveau logo, avec son favicon, placé à côté du nom sur les pages de connexion.
- Export des données : le profil propose une archive ZIP de fichiers CSV, lisibles par les tableurs
  français, avec les portfolios, comptes, opérations et axes, archivés compris.
- Suppression de son compte, après saisie du mot de passe : toutes ses données sont supprimées. Le
  compte administrateur ne peut pas être supprimé.
- Après la connexion, on revient sur la page demandée.
- Les pages de confirmation du mot de passe, de vérification de l'adresse et de réinitialisation,
  ainsi que les mails, sont en français.

### 💥 Changements majeurs

- Les pages métier demandent une adresse e-mail vérifiée. Une migration marque comme vérifiés les
  utilisateurs existants, et une nouvelle adresse saisie dans le profil reçoit un lien de
  vérification.
- L'inscription ne connecte plus : elle envoie le lien de vérification, et la connexion se fait en le
  suivant.
- Le profil redemande le mot de passe au-delà de trois heures.
- L'extension PHP `zip` est requise, pour l'export des données.

### 🔒 Sécurité

- L'inscription et « Mot de passe oublié » répondent de la même façon, qu'une adresse ait un compte
  ou non. Le titulaire d'une adresse déjà inscrite est prévenu par mail.
- Les formulaires d'authentification ont des limites de débit (connexion, inscription,
  réinitialisation, confirmation et changement du mot de passe, suppression du compte). Au-delà, le
  formulaire indique le délai d'attente.
- L'inscription crée l'utilisateur à partir des seules valeurs validées, et l'identifiant d'une
  opération ne peut plus être fixé par le formulaire.
- Les tests vérifient davantage le cloisonnement entre utilisateurs, l'échappement des saisies et la
  protection des comptes.

### 🐞 Corrections

- L'ancienne adresse d'un portfolio, avec un identifiant non numérique (`/portfolio/abc`), provoquait
  une erreur 500.

### ⚙️ Améliorations internes

- Ajout de la dépendance de plateforme `ext-zip` (`*`), pour l'export des données.
- Dépendances directes, Composer et npm, à jour : aucune version n'a changé depuis v1.3.1.

## v1.3.1 (2026-11-08)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.3.1, une version corrective du sélecteur de mois arrivé
avec v1.3.0.

### 🐞 Corrections

- Sélecteur de mois : un clic sur le chevron, à droite du champ, ouvre maintenant la fenêtre de choix
  des mois, comme le reste du champ.

## v1.3.0 (2026-11-08)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.3.0, qui rend les comptes plus faciles à trouver et les
opérations plus faciles à lire :

- recherche des comptes d'un portfolio par nom et par mois d'opération ;
- un nouveau sélecteur de mois ;
- toutes les opérations d'un compte sur une seule page, au choix ;
- une répartition analytique plus lisible, avec la part du montant de chaque axe.

### ✨ Nouvelles fonctionnalités

- Recherche des comptes : la page d'un portfolio filtre ses comptes, actifs et archivés, sur leur nom,
  au fil de la saisie, et sur un mois d'opération. Les deux filtres se cumulent, et un résumé donne le
  nombre de comptes trouvés.
- Sélecteur de mois : une année et ses douze mois dans une fenêtre, à la souris comme au clavier. Seuls
  les mois où le portfolio a des opérations se choisissent.
- Bouton « Tout » à côté de la pagination des opérations : toutes les opérations d'un compte
  s'affichent sur une page, avec leur nombre, et la page s'ouvre sur l'opération modifiée. Le choix
  vaut pour tous les comptes, le temps de la session.
- Répartition analytique : elle se range sous le montant de l'opération, chaque ligne affiche sa part
  du montant, et Entrée dans un pourcentage ajoute une ligne. L'infobulle de la liste des opérations
  donne aussi la part du montant de chaque axe, avec la devise.
- L'opération qui vient d'être modifiée se distingue du message de confirmation par l'accent indigo
  de l'interface.
- Les formulaires ne placent plus le curseur dans un champ à l'ouverture de la page.

### 🐞 Corrections

- Une recherche de compte mal formée dans l'adresse (`?search[]=…`) provoquait une erreur 500 : elle
  est désormais ignorée.

### ⚙️ Améliorations internes

- L'intégration continue lance aussi les tests JavaScript (`npm run test:js`, par le lanceur intégré
  à Node).

## v1.2.0 (2026-11-08)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.2.0, la plus grande version à ce jour :

- l'analytique : chaque portfolio définit ses axes, et une opération se répartit entre eux ;
- les comptes archivés restent consultables et se désarchivent ;
- une interface revue, en thème clair, sombre ou celui du système ;
- un déploiement par les archives des releases, avec sauvegarde de la base et retour arrière ;
- Laravel 13 et PHP 8.5.

### ✨ Nouvelles fonctionnalités

- Analytique : chaque portfolio a ses axes (par exemple « Logement », « Loisirs »), créés, modifiés et
  archivés depuis sa page. Une opération se répartit entre plusieurs axes, en pourcentages qui
  totalisent 100 % ; la liste des opérations marque d'une pastille les opérations réparties, avec le
  détail par axe au survol.
- Comptes archivés : la page d'un portfolio les liste sous les comptes actifs, dans une section repliée
  par défaut. Leurs opérations se consultent en lecture seule, et un compte archivé se désarchive.
- Thème clair, sombre ou celui du système, au choix dans le menu utilisateur, sur toutes les pages,
  pages d'erreur comprises.
- Confirmation des archivages et suppressions dans une boîte de dialogue.
- Bouton pour afficher le mot de passe sur la page de connexion.
- Interface revue : cartes en trois parties (en-tête, contenu, actions), icônes Lucide, infobulles,
  messages avec icône selon leur type, solde des comptes signé et coloré, signe moins typographique
  « − » devant les montants négatifs, barres de défilement fines.
- Commande `php artisan db:dump`, qui écrit une copie SQL complète de la base.
- Déploiement : la CI ajoute à chaque release une archive de l'application prête à servir, avec son
  empreinte SHA-256. Le script `deploy.sh`, donné dans le README, l'installe sur le serveur : il
  sauvegarde la base, passe les migrations, bascule vers la nouvelle version d'un coup, crée le service
  du worker de file, et revient à une version précédente sur demande.

### 💥 Changements incompatibles

- PHP 8.5 est requis (8.2 auparavant), avec les extensions `intl` et `pdo_mysql`.
- Les modèles de configuration deviennent `.env.model-dev` et `.env.model-prod`.
- L'administrateur se crée depuis les variables `APP_ADMIN_LAST_NAME`, `APP_ADMIN_FIRST_NAME`,
  `APP_ADMIN_EMAIL` et `APP_ADMIN_PASSWORD`, obligatoires et sans valeur par défaut. `APP_ADMIN_EMAIL`
  reçoit aussi les mails de portfolio.
- La session est enregistrée en JSON : les utilisateurs connectés devront se reconnecter.
- La sauvegarde de la base demande le privilège MySQL `SHOW_ROUTINE` (MySQL 8.0.20 ou plus récent).

### 🐞 Corrections

- La description d'un compte est enregistrée et affichée.
- Après l'ajout d'une opération, la redirection mène au compte, avec son message.
- La description d'un portfolio reste lisible au survol.

### 🔒 Sécurité

- Les comptes et les opérations sont réservés à leur propriétaire : un utilisateur pouvait auparavant
  consulter, modifier ou supprimer ceux d'un autre utilisateur à partir de leur identifiant.
- Le mode débogage est désactivé quand `APP_DEBUG` manque.
- Le cache et la session ne désérialisent plus aucune classe PHP.
- Les modèles de configuration ne contiennent plus de clef d'application ni d'identifiants
  d'administrateur.
- La sauvegarde transmet le mot de passe de la base par un fichier temporaire, hors de la ligne de
  commande.

### ⚙️ Améliorations internes

- Dépendances PHP :
  - `laravel/framework` v11.41.3 → v13.40.0 ;
  - `laravel/tinker` v2.10.1 → v3.0.2 ;
  - `mallardduck/blade-lucide-icons` ajouté (2.0.42), pour les icônes Lucide ;
  - développement : `pestphp/pest` v3.7.4 → v5.3.5, `pestphp/pest-plugin-laravel` v3.1.0 → v5.0.1,
    `barryvdh/laravel-debugbar` v3.14.10 → v4.4.5, `laravel/pail` v1.2.2 → v1.2.7, `laravel/pint`
    v1.20.0 → v1.32.1, `mockery/mockery` 1.6.12 → 1.6.15 ;
  - développement : `laravel/boost` ajouté (v2.10.4) ;
  - développement : `laravel/breeze` (v2.3.3) retiré, son code d'authentification faisant désormais
    partie du projet ; `laravel/sail` (v1.41.0) retiré, inutilisé ; `nunomaduro/collision` retiré
    des dépendances directes, toujours installé par Pest.
- Dépendances front :
  - `tailwindcss` 3.4.17 → 4.3.5, avec `@tailwindcss/vite` (4.3.5), qui remplace `postcss` (8.5.1)
    et `autoprefixer` (10.4.20) ;
  - `vite` 6.0.7 → 8.3.6, `laravel-vite-plugin` 1.1.1 → 3.2.1 ;
  - `alpinejs` 3.14.8 → 3.17.5, `@tailwindcss/forms` 0.5.10 → 0.5.11, `concurrently` 9.1.2 → 10.0.7 ;
  - `axios` (1.7.9) retiré, inutilisé.
- Intégration continue GitHub Actions : style (Pint), build du front et tests sur MySQL, à chaque push et
  pull request.
- Les tests tournent sur leur propre base, `portfolios_test`.
- Le projet s'appelle Portfolios (paquet Composer `sfaut/portfolios`).

### 📝 Documentation

- README : présentation, déploiement (serveur, Nginx, certificat, worker, sauvegardes, droits) et
  développement.
- Changelog, licence MIT, modèles de configuration commentés section par section.

## v1.1.1 (2025-02-09)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.1.1, qui soigne la liste des opérations d'un compte.

### ✨ Nouvelles fonctionnalités

- La pagination affiche le numéro de la dernière page.
- Un message signale un compte sans opération.
- Nouveau style du lien de modification d'une opération.

### 🐞 Corrections

- Le message affiché après l'enregistrement d'une opération reste visible après la redirection vers
  la dernière page.

### ⚙️ Améliorations internes

- Couleurs sémantiques dans les styles.

## v1.1.0 (2025-02-08)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.1.0 :

- les opérations d'un compte sont paginées ;
- la page d'un portfolio affiche aussi la date de la première opération de chaque compte.

### ✨ Nouvelles fonctionnalités

- Styles harmonisés entre la liste des portfolios et la page d'un portfolio.

## v1.0.4 (2025-02-03)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.0.4, version de corrections.

### 🐞 Corrections

- Nom de la table des verrous du cache.
- La confirmation de l'archivage d'un compte se déclenche à l'envoi du formulaire (`onsubmit`), et
  non plus au clic (`onclick`).

### ⚙️ Améliorations internes

- La colonne `operations.description` passe de 100 à 150 caractères et devient `operations.label`.

## v1.0.3 (2025-02-02)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.0.3, qui rend l'archivage des comptes fonctionnel.

### ✨ Nouvelles fonctionnalités

- Retour à la ligne dans les colonnes des comptes.
- Confirmation avant l'archivage d'un compte.

### 🐞 Corrections

- Vue SQL créée par les migrations.
- Archivage des comptes.

## v1.0.2 (2025-02-01)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.0.2, qui revoit l'inscription, la connexion et les
portfolios.

### ✨ Nouvelles fonctionnalités

- Vues, contrôleurs et validations de l'inscription et des portfolios revus.
- Mise en page des pages invité revue.
- Connexion et authentification revues.
- Messages d'erreur traduits.

## v1.0.1 (2025-01-26)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.0.1, version de corrections.

### 🐞 Corrections

- Mise à jour du profil.
- Migrations.

## v1.0.0 (2025-01-26)

### 🚀 Lancement

Nous sommes fiers d'annoncer Portfolios v1.0.0, première version publiée.
