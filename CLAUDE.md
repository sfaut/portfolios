# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projet

Portfolios (dépôt et paquet Composer `sfaut/portfolios`) : gestionnaire de portefeuilles et de comptes.
Chaque utilisateur possède des portfolios, qui regroupent des comptes, qui regroupent des opérations
(montants datés).

Pile : PHP 8.5+, Laravel 13, Blade + Alpine.js + Tailwind CSS 4 (base Breeze), Pest 5, MySQL, Vite 8.
Interface en français (`APP_LOCALE=fr`, fuseau `Europe/Paris`), extensions `intl`, `zip`, `gd` et `exif` requises (`zip` pour l'export des données, `gd` et `exif` pour les photos de profil). Tout montant
affiché passe par la macro `Number::amount($montant, signed: false, currency: '')` (déclarée dans
`AppServiceProvider`) : deux décimales en français, signe moins typographique « − » (U+2212) devant un
négatif, jamais le tiret « - » de `Number::format()` / `Number::currency()` ; `signed` ajoute « + »
devant un positif (solde des opérations), `currency: 'EUR'` la devise.

## Commandes

- Installation : `composer install`, `npm install`, copier `.env.model-dev` (ou `.env.model-prod`) vers
  `.env` et y renseigner les identifiants, puis `php artisan key:generate` et `php artisan migrate --seed`. `UserSeeder`, appelé par `DatabaseSeeder`, crée l'administrateur
  (id 1) depuis les quatre variables `APP_ADMIN_*`, lues par `config('app.admin')`. Elles sont
  obligatoires et sans valeur par défaut, pour qu'aucune installation ne garde des identifiants publiés
  avec le code : une valeur absente ou invalide arrête le seed, avec le nom des variables à corriger.
  Une base qui a déjà un administrateur (`is_admin`) est laissée telle quelle, sans contrôle des
  variables.
  `config('app.admin.email')` est aussi le destinataire des mails de portfolio.
- Développement : `composer dev` lance ensemble `php artisan serve`, `php artisan queue:listen`,
  `php artisan pail` et `npm run dev`, par concurrently 10 (Node 22 au moins). Le worker de file est
  nécessaire : les mails de portfolio passent par la file `database`. L'application répond sur
  http://portfolios.localhost:10000 : les navigateurs résolvent `*.localhost` vers 127.0.0.1, sans
  fichier `hosts`, et `php artisan serve` écoute sur le port `SERVER_PORT` du `.env`, que `APP_URL`
  reprend. Port occupé au démarrage, `serve` passe sans prévenir au suivant (10001…) : `APP_URL` ne
  correspond plus et les liens générés visent le mauvais port.
- Build front : `npm run build` (Node 20.19 ou 22.12 au moins, pour Vite 8).
- Style : Pint, sans `pint.json` (preset `laravel`), voir « Conventions » ci-dessous.
- Tests : `php artisan test`, un fichier `vendor/bin/pest tests/Feature/ProfileTest.php`, un test
  `php artisan test --filter="profile page is displayed"`. Tests JavaScript : `npm run test:js`, par le
  lanceur intégré à Node (`node:test`, sans dépendance), sur `tests/js/**/*.test.js` ; ils ne portent
  que sur des modules purs, sans DOM ni Alpine (`resources/js/month-picker-rules.js`,
  `date-picker-rules.js`…). Les cas que PHP
  et JavaScript doivent appliquer à l'identique sont dans `tests/fixtures/*.json`, lus par les deux.
- GitHub Actions : `.github/workflows/ci.yaml` vérifie le code (Pint, ShellCheck sur `bin/*.sh`,
  build du front, tests JavaScript, tests Pest sur un service MySQL) à chaque push sur `master` et à chaque pull request. À la
  publication d'une release, il
  construit en plus l'archive de déploiement (`composer install --no-dev`, `npm run build`), qu'il
  ajoute à la release avec son empreinte SHA-256 (`portfolios-vX.Y.Z.tar.gz`, `.sha256`).
- Production (serveur perso) : le déploiement est décrit dans `README.md`, et le script est
  `bin/deploy.sh`, installé sur le serveur depuis le tag d'une version (section « Script de
  déploiement »), jamais depuis l'archive. Une version qui le modifie le signale dans son changelog,
  pour qu'il soit réinstallé. Lancé à la main sur le serveur (`deploy.sh vX.Y.Z`), il installe
  l'archive de la release dans `/var/www/portfolios/releases/vX.Y.Z`, la relie à `shared/.env` et
  `shared/storage`, lance `optimize`, sauvegarde la base (`php artisan db:dump`, compressée dans `backups/`), lance
  `migrate --force`, bascule le lien `current`, puis redémarre le worker de file, dont il crée le
  service systemd (`portfolios-queue`) s'il n'existe pas. Au premier lancement, il crée `shared/.env`
  depuis `.env.model-prod` de la version, avec une `APP_KEY` générée une fois pour toutes, et s'arrête.
  `optimize`
  met en cache la configuration, les routes, les vues et, par blade-icons, les icônes (`icons:cache`,
  sans quoi chaque rendu relit les SVG sur le disque). Une migration doit rester compatible avec la
  version précédente, qui peut revenir par `deploy.sh` sur le schéma migré.
- Production (Laravel Cloud) : README, section « Déploiement sur Laravel Cloud ». L'environnement suit
  `master`, sans push to deploy ; `bin/deploy-laravel-cloud.sh vX.Y.Z`, lancé depuis un clone, vérifie
  le tag (commit sur `origin/master`, release avec son archive), puis appelle le deploy hook avec le
  commit du tag (`commit_hash`). L'adresse du hook est lue dans le `.env` du clone
  (`LARAVEL_CLOUD_DEPLOY_HOOK`, vide dans `.env.model-dev`), jamais par l'application. Les photos y
  vont dans un bucket privé (`AVATAR_DISK`).
- Sauvegarde : `php artisan db:dump` (`App\Console\Commands\DumpDatabase`) écrit sur la sortie standard
  une copie complète de la base MySQL par `mysqldump` (routines, déclencheurs, événements, `DROP` puis
  `CREATE DATABASE`). Les accès lui passent par un fichier d'options temporaire, jamais par la ligne de
  commande. Le compte MySQL a besoin du privilège `SHOW_ROUTINE` pour copier les routines d'un autre
  compte.
- Changelog : `CHANGELOG.md` à la racine, en français, une entrée par version (`## vX.Y.Z (YYYY-MM-DD)`,
  sections `###` à icône, « 🚀 Lancement » en premier), la plus récente en tête. Les versions v1.0.0 à
  v1.1.1 y reprennent les notes de leur release GitHub, traduites de l'anglais.
- Laravel Boost (dépendance de dev) : consignes ajoutées à la fin de ce fichier, skills dans
  `.claude/skills`, serveur MCP `laravel-boost` déclaré dans `.mcp.json`. Ses choix sont dans `boost.json`
  et `config/boost.php`, qui écarte la consigne `deployments` (Laravel Cloud). `php artisan boost:update`
  régénère consignes et skills ; `composer update` le lance. Lancé à la main, il propose les skills des
  paquets non retenus (celui de debugbar) : `--no-interaction` passe la question.

### Tests

- Les tests ont leur propre base, `portfolios_test`, fixée par `phpunit.xml` (`DB_DATABASE`) sur la
  connexion MySQL du `.env`, et à créer à l'installation, sur le modèle de `portfolios_dev`
  (`CREATE DATABASE portfolios_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci`).
  `tests/Pest.php` applique `RefreshDatabase` à `tests/Feature`, qui vide cette base à chaque lancement
  (`migrate:fresh`) ; la base de développement n'est pas touchée. Une variable passée par le shell
  l'emporte sur `phpunit.xml` (`DB_DATABASE=<base> php artisan test`), `force` n'y étant pas activé.
- Les tests demandent MySQL, à cause des vues créées par les migrations (`CREATE OR REPLACE VIEW`,
  fonctions de fenêtrage), et le manifeste Vite (`npm run build`) pour les vues Blade.
- `mockery/mockery` reste nécessaire sans aucune doublure dans les tests : `RefreshDatabase` lance
  `migrate:fresh` par `artisan()`, qui simule la sortie de la console avec Mockery.
- `tests/Feature/OwnershipTest.php` vérifie le cloisonnement entre utilisateurs, route par route : une
  nouvelle route sur un portfolio, un compte ou une opération s'y ajoute, dans le jeu de l'autre
  utilisateur (404) et dans celui des invités (redirection vers la connexion). Il vérifie aussi qu'une
  clef inattendue du formulaire (`user_id`, `portfolio_id`, `account_id`) ne déplace aucune donnée
  chez un autre utilisateur.
- `tests/Feature/EscapingTest.php` vérifie l'échappement des saisies (noms, descriptions, libellés,
  recherche) dans les pages, les messages flash et les mails : une nouvelle page qui affiche une saisie
  s'y ajoute.
- `tests/Feature/AdminIntrusionTest.php` attaque la zone d'administration comme un utilisateur
  malveillant : méthodes et variantes d'adresse, `is_admin` glissé dans les formulaires, session
  (adresse non vérifiée, mot de passe non confirmé, déconnexion, rétrogradation), paramètres forgés,
  fuite de secrets, en-têtes. Une nouvelle route d'administration s'y ajoute.

## Conventions

- Les directives générales (documentation, nommage, règles de migration…) s'appliquent au code nouveau ou
  modifié. Le code existant qui ne les suit pas (docblocks absents, `timestamps(precision: 6)`, colonnes
  `nullable`…) reste en l'état. Un changement de schéma passe par une nouvelle migration, les migrations
  déjà exécutées restant intactes.
- Le format Laravel de Pint (preset `laravel`, sans `pint.json`) fait foi, docblocks compris, et prime
  ici sur les directives générales de documentation. Il retire les `@param` / `@return` qui ne font que
  répéter le type natif (dont `@return void`) et aligne les tags (`@param  Type  $nom  Description.`), une
  description longue commençant sur la ligne du tag. Les descriptions restent obligatoires.
- Tout le code est formaté : `vendor/bin/pint --test` doit sortir en 0. Formater les fichiers modifiés
  avec `vendor/bin/pint --dirty`.
- Les consignes de Boost en fin de fichier et `.claude/skills` sont générées par Boost et réécrites par
  `boost:update` : ne pas les modifier à la main. Les directives générales et le reste de ce fichier
  priment sur elles, par exemple pour les propriétés promues (une par ligne), la longueur des colonnes
  `string`, le `RefreshDatabase` de `tests/Pest.php` ou le contrôle par `pint --test`.
- Boost encadre ses consignes par la balise `laravel-boost-guidelines`, ouvrante et fermante, et
  `boost:update` remplace tout ce qui va de la première balise ouvrante à la première fermante. Ce texte
  n'écrit donc jamais la balise ouvrante avec ses chevrons : Boost y verrait le début de son bloc.

## Architecture

### Domaine et données

- Hiérarchie `User` → `Portfolio` → `Account` → `Operation`, clefs étrangères en cascade.
  `Portfolio` et `Account` utilisent `SoftDeletes` (l'interface parle d'« archivage ») ; `Operation` est
  supprimée physiquement. Les horodatages sont en précision 6, d'où `$dateFormat = 'Y-m-d H:i:s.u'` sur
  les modèles, `User` compris : sans lui, Laravel écrit les dates à la seconde (`.000000`). Un nouveau
  modèle le reprend et s'ajoute à `tests/Feature/TimestampPrecisionTest.php`.
- Lecture et écriture passent par des modèles distincts. Les vues SQL `account_reports` (solde, nombre
  d'opérations, première et dernière date) et `operation_reports` (cumul `amount_running_sum` par compte,
  ordonné par `delivery_at`, `id`) sont créées dans la migration
  `database/migrations/2024_12_21_222937_create_operation_table.php` (`operation_reports` recréée par
  `2026_11_10_003926_add_is_ticked_to_operations_table.php`) et lues par les modèles en lecture
  seule `AccountReport` / `OperationReport`. L'affichage les utilise (`Portfolio::accountsReport()`,
  `Account::operationsReport()`) ; les écritures passent par `Account` / `Operation`. Changer les colonnes
  de `accounts` ou `operations` demande de recréer ces vues dans une nouvelle migration.
- Pointage : une opération est pointée ou non (`operations.is_ticked`, tinyint non signé, 0 par
  défaut, converti en booléen), au sens que l'utilisateur lui donne. `<x-tick-box :operation
  :disabled>` en fait une case dans la liste d'un compte et dans le Grand livre : avec JavaScript
  (composant Alpine `tickBox`, `resources/js/tick-box.js`), son étiquette couvre la cellule
  (`relative`), un clic enregistre aussitôt (`PATCH operation.tick`, `OperationController::tick()`,
  réponse JSON) et un toast confirme, avec « Annuler » ; un échec remet la case et le dit dans un
  toast `danger`. La requête porte l'état voulu, jamais une inversion, et un clic pendant l'envoi
  part à sa fin. Sans JavaScript, un bouton envoie le formulaire, qui revient à la page avec le
  flash `toast`. Le formulaire d'opération a aussi sa case « Pointée » (`is_ticked`, facultatif).
  Compte archivé : case désactivée, `tick` en 404. Les deux exports ont une colonne `pointée` (0 / 1).
- Analytique : un portfolio a des axes (`AnalyticAxis`, table `analytic_axes`, nom unique dans le
  portfolio, archivés par `SoftDeletes`) ; une ligne analytique (`AnalyticLine`) attribue à un axe une
  part en pourcentage (deux décimales) d'une opération. Les lignes d'une opération sont facultatives ;
  présentes, elles portent des axes distincts et totalisent exactement 100 %, contrôle fait en centièmes
  entiers par `OperationRequest::after()`. Une modification d'opération remplace ses lignes. Un axe
  archivé ne se choisit plus, sauf sur une opération qui l'utilise déjà, et reste affiché sur ses
  lignes (`AnalyticLine::analyticAxis()` inclut les archivés).
  Un axe a une icône et une couleur (colonnes `icon` et `color`, défauts `tag` et `indigo`).
  L'icône vient d'une sélection de Lucide, `App\Support\AxisIcons` : thèmes, nom français et
  mots-clefs de recherche de chaque icône, liste blanche de la validation. Une icône s'y ajoute sous
  son nom Lucide ; `tests/Unit/AxisIconsTest.php` vérifie qu'elle existe, et que les familles musique
  et jeux (dont les six `chess-*`) restent complètes. La couleur est `App\Enums\AxisColor`, les 17
  familles vives de Tailwind et gray, ses classes écrites en entier dans des `match` pour que
  Tailwind les trouve (`badgeClasses()`, `textClasses()`, `onDarkClasses()`, `barClasses()`).
  `<x-axis-badge :axis>` (`App\View\Components\AxisBadge`) affiche l'axe partout : pastille colorée,
  icône devant le nom, « (archivé) » ; `icon-only` ne garde que l'icône colorée, `on-dark` sur le
  fond sombre d'une infobulle. Il accepte un modèle ou une ligne de requête `toBase()` (name, icon,
  color, archived). Le formulaire de l'axe choisit les deux par `<x-axis-appearance>` (composant
  Alpine `axisAppearance`, recherche sans casse ni accents dans `axis-appearance-rules.js`, testée
  par Node) : aperçu de la pastille qui suit le nom saisi, couleurs en pastilles rondes, puis grille
  d'icônes teintée de la couleur choisie, avec recherche et onglets de thème ; ce sont des radios
  natifs, envoyés sans JavaScript. « Non réparti » se montre en gris avec `circle-dashed`.
  Un compte a une répartition analytique par défaut, facultative et ordonnée : table pivot
  `account_analytic_axis` (`percentage`, total exactement 100 % dès qu'il y a une ligne, et
  `position`, à partir de 0), relation `analyticAxes()` sur `Account` et sur `AccountReport`
  (archivés inclus, par position, chargée d'un coup par la liste du portfolio). Le formulaire du
  compte la saisit par `<x-analytic-lines sortable>` (`analytic_axes[i][analytic_axis_id|percentage]`,
  la position étant l'ordre d'envoi, aucune ligne la retire), parmi
  `Portfolio::assignableAnalyticAxes()` : les axes non archivés, plus les archivés déjà affectés au
  compte, seule liste blanche de `AccountRequest`. Les lignes s'y réordonnent par une poignée
  (`@alpinejs/sort`, `x-sort`), ou par ses flèches haut / bas au clavier. Elle n'a d'autre effet que
  de préremplir une nouvelle opération du compte (`OperationController::analyticForm()`, axes
  archivés exclus) ; les opérations existantes ne changent pas. L'ordre des axes du compte est celui
  de leur affichage : `<x-axis-icons :axes>` les montre après le nom du compte (carte de la liste,
  titre de la page du compte), chaque icône dans un `<x-tooltip>` qui donne son nom ; `focusable`
  hors d'un lien. Les lignes d'une opération s'affichent de la plus lourde à la plus légère, à
  égalité dans l'ordre des axes de son compte, puis par nom : portée `AnalyticLine::inDisplayOrder()`,
  à poser sur chaque chargement d'affichage (liste du compte, Grand livre, formulaire en
  modification), jamais dans la relation `Operation::analyticLines()`, qui sert aussi aux
  suppressions. Le tri « Axes » du Grand livre suit le premier axe ainsi affiché. L'export écrit la
  répartition dans `axes-comptes.csv`.
- `bootstrap/app.php` redéfinit la pile de middlewares globaux sans `ConvertEmptyStringsToNull` : un champ
  vide arrive en `''`, en accord avec les colonnes `description` à valeur par défaut `''`.
- La session est sérialisée en JSON (`config/session.php`) et le cache ne désérialise aucune classe PHP
  (`serializable_classes` à `false` dans `config/cache.php`) : n'y ranger que des scalaires et des
  tableaux. Un objet reviendrait en tableau depuis la session, et en `__PHP_Incomplete_Class` depuis le
  cache.

### Appartenance des données

- Le cloisonnement entre utilisateurs repose sur des portées globales, en chaîne :
  `App\Models\Scopes\AuthUserScope` (`where user_id = auth()->id()`) sur `Portfolio`, puis
  `App\Models\Scopes\AuthUserParentScope` sur `Account` (relation `portfolio`) et `Operation` (relation
  `account`), qui exige un parent visible, archivé ou non ; de même `AnalyticAxis` (relation `portfolio`)
  et `AnalyticLine` (relation `operation`, parent sans `SoftDeletes`, que la portée accepte). La liaison de modèle des routes renvoie donc
  404 pour la donnée d'un autre utilisateur. Il n'y a ni policy ni gate, et les `authorize()` des
  FormRequests renvoient `true`.
- Un nouveau modèle rattaché à un portfolio reçoit la même portée (`AuthUserParentScope` vers son parent).
  `AccountReport` / `OperationReport` se passent de portée : on les atteint par les relations d'un parent
  déjà filtré.
- Hors requête authentifiée (file, console, tinker, seeder), `auth()->id()` vaut `null` : les requêtes sur
  `Portfolio`, `Account` et `Operation` ne renvoient rien sans `withoutGlobalScope(…)`.

### HTTP et vues

- Authentification (Breeze, `routes/auth.php`) : aucune réponse ne dit si une adresse a un compte.
  L'inscription répond toujours par la page de connexion et le même message, sans connecter : à une
  adresse nouvelle, elle crée l'utilisateur, qui reçoit le lien de vérification et se connectera en le
  suivant (la connexion rouvre la page demandée) ; à une adresse déjà inscrite, elle ne change rien et
  prévient son titulaire (`RegistrationAttempted`). « Mot de passe oublié » répond de même dans tous les
  cas (`lang/fr/passwords.php`). `User` implémente `MustVerifyEmail`, et une adresse non vérifiée mène
  à la page `verification.notice`. Le profil reste hors de `verified`, pour corriger une adresse mal
  saisie ; une nouvelle adresse y perd sa vérification et reçoit un lien. Il passe en revanche par
  `password.confirm` : le mot de passe est redemandé au-delà de trois heures (`auth.password_timeout`),
  et un test du profil pose `auth.password_confirmed_at` dans la session. Les formulaires d'auth ont
  leurs limites de débit (`throttle:<nom>`), déclarées dans `AppServiceProvider::registerRateLimiters()` :
  au-delà, le formulaire revient avec le délai d'attente sous un champ. Les mails d'auth partent
  pendant la requête : `MAIL_FROM_ADDRESS` doit être renseignée, sans quoi l'inscription répond 500
  (`phpunit.xml` en fixe une pour les tests).
- Toutes les routes métier sont dans `routes/web.php`, sous les middlewares `auth` et `verified` ;
  `routes/auth.php` y est inclus. Seule la création est imbriquée (`/portfolio/{portfolio}/account/create`,
  `/account/{account}/operation/create`) ; les autres actions sont à plat (`/account/{account}/edit`…).
  Noms de route : `portfolio.*`, `account.*`, `operation.*`, `ledger.*`, `analytic-axis.*`, `profile.*`. Les axes
  analytiques ont aussi leur liste imbriquée (`/portfolio/{portfolio}/analytic-axes`), faute de page
  parent qui l'accueille ; `edit`, `update` et `restore` portent `->withTrashed()`, comme pour les
  comptes. La page d'accueil après connexion est `portfolio.index` : le projet n'a pas la route
  `dashboard` du code Breeze d'origine.
- Navigation : la barre du haut (`layouts/navigation.blade.php`) dit où l'on est, le bandeau de la page
  ce qu'est la page. La barre porte le logo, qui mène à la liste des portfolios, et le menu de
  l'utilisateur. Dans un portfolio, elle porte aussi le sélecteur de portfolio, de largeur fixe (ses
  portfolios, puis modifier, tous, créer), et `<x-portfolio-nav>`, toujours à la même place : ces deux
  éléments passent sur une seconde ligne sur un écran étroit, et `scrollbar-gutter: stable` empêche
  la barre de défilement de les décaler d'une page à l'autre. Le portfolio et la section viennent de
  `App\Support\PortfolioContext`, qui les déduit du modèle lié par la route (portfolio, compte,
  opération ou axe) et de son nom. Un composeur de vue (`AppServiceProvider`) les passe à la barre.
  Sections : tableau de bord ; comptes (liste, compte, opération) ; grand livre ; axes analytiques ; aucune sur la
  modification du portfolio. Hors d'un portfolio (liste, création, profil), la barre n'a que le logo
  et le menu. Le titre du bandeau nomme la page, jamais le portfolio, que montre la barre. Sous le
  titre, `<x-breadcrumb :links="[url => texte]">` mène de la section à la page parente
  (« Comptes › Compte courant »), absent sur les pages de premier niveau. À droite viennent les
  actions de la page. Une nouvelle page d'un portfolio entre dans `PortfolioContext` (section) et
  dans `tests/Feature/NavigationTest.php`.
  Le profil tient en quatre pages, toutes derrière `password.confirm`, dans `<x-profile-layout>` (titre
  de la page, navigation latérale `<x-side-nav>` dont la page courante porte `aria-current="page"`) :
  `profile.edit` (photo, informations), `profile.preferences` (thème), `profile.password`,
  `profile.account` (export des données, puis suppression du compte).
  Photo de profil (`ProfileAvatarController`, `ProfileAvatarRequest`) : JPEG, PNG, GIF, WebP, et AVIF
  si GD le lit, 8 Mo et 24 Mpx au plus, contrôlés sur l'en-tête avant tout décodage. La carte offre
  deux entrées, « Importer une photo » et « Prendre une photo », vers une même boîte
  (`profile/partials/avatar-dialog`), menée par le composant Alpine `avatarEditor`
  (`resources/js/avatar-editor.js`, calculs purs dans `avatar-crop-rules.js`, testés par Node).
  Sa scène carrée garde son cercle : vidéo de la caméra en miroir, déclenchée aussitôt ou après un
  retardateur de 3 s, puis image à recadrer ; la photo prise est en vue réelle, sans miroir. La
  caméra (`getUserMedia`) demande un contexte sécurisé (HTTPS ou `*.localhost`) : ailleurs (adresse IP
  en HTTP…), le bouton reste visible, désactivé, avec la raison écrite dessous ; un navigateur sans
  caméra le cache (`cameraSupport()`). Elle s'éteint dès la prise de vue ou la fermeture. La boîte met à la place du fichier
  un carré de 512 pixels ; sans JavaScript, un champ du `<noscript>` envoie la photo telle quelle.
  `App\Support\AvatarImage` décode toute photo reçue par GD, applique l'orientation EXIF, recadre
  au centre et réencode en WebP sans perte de 512 × 512 (le navigateur envoie un PNG) : aucune
  métadonnée ni contenu glissé ne passe. Le fichier
  va sur le disque privé des photos, `User::avatarDisk()` (`avatars/{id}-{aléatoire}.webp`, à plat,
  colonne `users.avatar_path`, '' sans photo), nommé par `AVATAR_DISK` (`filesystems.avatar_disk`) :
  `local` sur un serveur, celui d'un bucket privé sur Laravel Cloud, configuré par Cloud
  (`LARAVEL_CLOUD_DISK_CONFIG`, avec `throw` faux : un envoi vérifie le retour de `put()`). L'ancien est supprimé au remplacement, au retrait et avec l'utilisateur
  (`User::booted()`). `profile.avatar` le sert au seul utilisateur connecté, sans identifiant dans
  l'adresse, versionné par `?v=` et gardé un an dans le cache privé du navigateur. L'envoi demande
  une adresse vérifiée ; il est limité par `throttle:avatar`.
  La suppression de compte utilisateur (`ProfileController::destroy()`) demande le mot de passe, puis
  supprime l'utilisateur et, par les clefs étrangères en cascade, toutes ses données, archivées
  comprises ; aucun mail de portfolio ne part alors, l'observateur n'étant pas appelé. Le compte
  administrateur (`is_admin`) ne se supprime pas : le profil ne le propose pas, et l'action le refuse.
  Le profil propose aussi de télécharger ses données (`profile.export`, `DataExportController`) : une
  archive ZIP de CSV construite par `App\Support\UserDataExport`, archivés compris, lue sur les tables
  et filtrée explicitement sur l'utilisateur. Tout CSV s'écrit par `App\Support\Csv`, qui suit les
  tableurs français (BOM, « ; », décimales à virgule, dates ISO) et neutralise par une apostrophe un
  texte qui commence comme une formule. Une nouvelle table de données de l'utilisateur y ajoute son fichier ; la photo y est
  `photo.webp`.
- Administration : routes `admin.*` sous `/admin`, derrière les middlewares `auth`, `admin`
  (`App\Http\Middleware\EnsureUserIsAdmin`), `verified` et `password.confirm`. `admin` répond à tout
  autre utilisateur la 404 d'une adresse inconnue, avant même la vérification de l'adresse, et pose
  `Cache-Control: no-store, private` et `X-Robots-Tag: noindex, nofollow` sur les réponses de
  l'administrateur. La dernière route du groupe renvoie 404 à toute autre adresse ou méthode de la
  zone, qui sans elle répondrait 405. Le menu de l'utilisateur ne montre l'entrée « État du serveur »
  qu'à l'administrateur. La page `admin.server` (`Admin\ServerStatusController`) affiche le relevé de
  `App\Support\ServerStatus\ServerStatus` : six sections (`StatusSection`) de lignes (`StatusItem`,
  niveau `ok` / `warning` / `danger`, jauge facultative), les alertes en tête. Seuils et règles dans
  son docblock : aucune valeur secrète affichée, toute mesure absente « Non disponible », le worker
  de file jugé par l'âge des tâches en attente, sans commande système. Les mesures de la machine
  (`/proc`, charge, disque) passent par des méthodes protégées, que `ServerStatusTest` remplace par
  une sous-classe liée dans le conteneur ; elles sont « Non disponible » sous Windows.
- Par entité : un contrôleur, une FormRequest partagée par `store` et `update` (avec `attributes()` en
  français), des vues `resources/views/<entité>/{index,show,create,edit}.blade.php` et un formulaire commun
  `partials/form.blade.php`.
- Tableau de bord d'un portfolio (`portfolio.dashboard`, `PortfolioController::dashboard()`) : sa page
  d'accueil, où mènent la liste des portfolios et l'ancienne adresse `/portfolio/{id}`. Il suit les
  ventes, selon la convention du métier : un montant négatif est une vente, comptée en valeur absolue,
  un montant positif un paiement, et un compte soldé à zéro a ses ventes payées. Les ventes se lisent
  par `App\Support\PortfolioSales` (comptes archivés compris, par `delivery_at`). En tête, les chiffres
  clefs à la date du jour (`SalesFigures`) : comptes actifs, ventes du mois en cours (à date), du mois
  précédent et des 12 derniers mois complets, chacune comparée à un an plus tôt (`SalesComparison`,
  tendance en icône et en couleur par `<x-trend>`), et reste à encaisser (soldes négatifs). Puis les
  ventes de la période (`SalesDashboard`), choisie dans le bandeau de la page par
  `<x-month-picker mode="range">` (paramètre `period`, borné aux mois d'opérations, une période
  refusée ignorée, par défaut de M − 13 à M, M étant le mois en cours ou celui de la dernière
  opération s'il est antérieur) ; les chiffres clefs restent au jour. Pour la période : total,
  graphe des ventes par mois, répartition analytique (arrondi de `AnalyticLine::amountShare()`,
  « Non réparti » en dernier). Une période qui dépasse aujourd'hui s'arrête à aujourd'hui, et sa
  comparaison au même jour un an plus tôt. Les graphiques se dessinent sans bibliothèque :
  `<x-chart.monthly-sales>` (`App\View\Components\Chart\MonthlySales`) met les ventes par mois en
  barres (léger dégradé vertical, du clair en bas au foncé en haut, à l'échelle du graphe), le même
  mois un an plus tôt en barres fantômes, et le cumul en courbe sur un axe secondaire ; ses deux axes
  ont le même nombre de pas ronds
  (`App\Support\ChartScale`), donc les mêmes lignes de repère. `<x-chart.step-line>`
  (`App\View\Components\Chart\StepLine`, courbe en paliers de valeurs cumulées), inutilisé, reste
  disponible. Les courbes sont en SVG étiré, les textes en HTML, les barres en HTML et CSS, avec
  `<x-tooltip>`.
- Comptes archivés : `portfolio/show` les liste dans une section « Comptes archivés », sous les comptes
  actifs, repliée par défaut (Alpine) et suivie de leur nombre. Les routes `account.show`, `account.edit`, `account.update` et `account.restore` (désarchivage,
  proposé par `account/edit` à la place de l'archivage) portent `->withTrashed()` ; `account.destroy` non.
  Les opérations d'un compte archivé sont en lecture seule : `operation.create` / `store` lient un compte
  non archivé, et `OperationController` répond 404 sur `edit` / `update` / `destroy`. La relation
  `Operation::account()` inclut les comptes archivés.
- Filtres des comptes : `portfolio/show` filtre ses comptes, actifs et archivés, sur le nom (paramètre
  `search`, `whereLike` aux jokers échappés, insensible à la casse et aux accents par la collation) et
  sur le mois (paramètre `month`, « 2026-11 » : comptes ayant au moins une opération dans le mois, par
  `delivery_at`). Les deux se cumulent. Le mois se choisit par `<x-month-picker>`, limité aux mois où
  le portfolio a des opérations : le contrôleur en fait la liste blanche d'une `MonthRestriction`,
  passée au composant, et ignore un mois qu'elle refuse (filtre en GET, sans erreur).
  Le composant Alpine `accountSearch` (`resources/js/account-search.js`) interroge la même route après
  300 ms de pause (aussitôt pour le mois), avec `X-Requested-With` : `PortfolioController::show()` ne renvoie alors que le
  fragment Blade `accounts` (`fragmentIf()`, en-tête `Vary`), qui remplace les résultats. Le résumé
  (« 2 comptes sur 14 ») se place à droite des filtres, sans changer la hauteur de la barre ; sans
  JavaScript, le formulaire recharge la page filtrée.
- Grand livre (`ledger.index`, `LedgerController`, `App\Support\Ledger`) : toutes les opérations d'un
  portfolio, comptes archivés compris, avec identifiant, compte, horodatage d'écriture (`created_at`), date
  effective, libellé, montant, axes et pointage. C'est une page large (`<x-app-layout wide>`), et la
  colonne des axes écrit chaque ligne analytique, sans infobulle : pastille (tronquée au-delà de
  8rem), part et montant (`AnalyticLine::amountShare()`), en colonnes de largeur fixe alignées d'une
  opération à l'autre. Compte et libellé ont une largeur minimale ; sur un écran étroit, le tableau
  défile. `LedgerFilters` lit les paramètres GET en filtre tolérant (une
  valeur refusée est ignorée) : `accounts[]`, `axes[]` (au moins un des axes, `none` pour les
  opérations non réparties), `written` et `effective` (plages `[start]` / `[end]`, bornées aux dates
  des opérations), `label`, `flow` (`sales` / `payments`), `amount[min]` / `amount[max]` (valeur
  absolue), `ticked` (`yes` / `no`), `sort` et `dir`. `Ledger` construit la requête sur `Operation` (portées conservées), trie
  sur chaque colonne (par défaut l'écriture la plus récente en tête, départage par identifiant, non
  répartis en dernier pour les axes), calcule les totaux filtrés (nombre, ventes, paiements, solde) et
  pagine par 50. Le bouton « Tout » garde sa préférence en session
  (`LedgerController::SHOW_ALL_OPERATIONS`), comme pour un compte. Le composant Alpine `ledger`
  (`resources/js/ledger.js`) recharge le fragment Blade `ledger` (totaux, outils, tableau) à chaque
  changement de filtre, de tri ou de page ; sans JavaScript, le formulaire, les en-têtes et la
  pagination rechargent la page. `ledger.export` télécharge la sélection triée en CSV, diffusée au fil
  de la lecture. Un nouveau filtre entre dans `LedgerFilters` (lecture, `filterQuery()`, `state()`),
  `Ledger::filtered()`, le formulaire, `ledger.js` et `tests/Feature/LedgerTest.php`.
- Répartition analytique du formulaire d'opération : `<x-analytic-lines :form>`, éditeur partagé avec
  la répartition par défaut d'un compte (options `name`, `amount`, `sortable`), contrôle du total
  partagé par `App\Http\Requests\Concerns\ValidatesAnalyticBreakdown`. Composant Alpine `analyticLines`
  (`resources/js/analytic-lines.js`, enregistré par `Alpine.data()` dans `app.js`), qui ajoute et retire
  les lignes, préremplit une nouvelle ligne du reste jusqu'à 100 % et affiche le total, dans la colonne
  des pourcentages. Entrée dans un pourcentage ajoute une ligne et en sélectionne le pourcentage, sans
  envoyer le formulaire. Chaque ligne montre aussi sa part du montant, au format de `Number::amount()`, que
  le composant recalcule en suivant le champ Montant du formulaire, hors de son bloc. La section se
  range sous ce champ, en retrait derrière un filet vertical. Ses données
  (axes proposés, lignes initiales ou renvoyées après une validation échouée) viennent de
  `OperationController::analyticForm()`, les erreurs de `$errors`, que le composant lit par clef
  (`analytic_lines.<i>.<champ>`). La liste des opérations (`account/show`) marque d'une pastille les
  opérations réparties, avec le détail par axe dans un `<x-tooltip>` : pourcentage et part du montant
  (`AnalyticLine::amountShare()`, même arrondi que le formulaire).
- `AccountController::show()` pagine les opérations par 20 et redirige vers la dernière page quand
  `page` est absent, en refaisant le flash de session (`reflash()`) pour garder le message. Le bouton
  poussoir « Tout », à côté de la pagination, envoie `all=1` ou `all=0` (formulaire GET) : la
  préférence est gardée en session (`AccountController::SHOW_ALL_OPERATIONS`, pour tous les comptes),
  puis la page se recharge sans le paramètre. Toutes à la fois, les opérations s'affichent sans
  pagination, avec leur nombre, et la page s'ouvre sur l'opération modifiée, centrée, ou à défaut au
  bas du tableau.
- Messages flash : la clef varie selon la vue qui l'affiche, `message` (vues portfolio), `alert`
  (`account/show`), `status` (vues Breeze). Un flash n'apparaît que si la vue de destination lit sa clef.
  Les messages de portfolio sont dans `lang/fr/portfolio.php` ; les autres sont écrits en dur dans les
  contrôleurs.
- Mise en page : `<x-app-layout>` → `layouts/app.blade.php` → `<x-empty-layout>` (HTML, `@vite`).
  Barre, bandeau et contenu ont la même largeur, `max-w-7xl` ; une page large (`<x-app-layout wide>`,
  le Grand livre) les passe ensemble à `AppLayout::WIDE_CONTAINER` (120rem et marges de la barre),
  que son contenu reprend. Les
  composants sont surtout anonymes (`resources/views/components`, dont `x-link.primary` /
  `x-link.secondary` avec `:target`, `x-text-input` / `x-textarea` pour les champs). `x-alert type="…"`
  (`info` par défaut, `success`, `warning`, `danger`) tient de sa classe `App\View\Components\Alert` ses
  couleurs, son icône Lucide et son rôle ARIA (`alert` pour `danger`, `status` sinon) ; il garde les
  retours à la ligne du message, sans ses blancs de début et de fin.
  Toute infobulle passe par `<x-tooltip>` (slot par défaut : déclencheur, slot `content` : contenu),
  sans attribut `title` : popover manuel ouvert par Alpine au survol et au focus, dans la couche
  supérieure (il échappe à un parent en `overflow-hidden`), placé par le positionnement d'ancre CSS
  au-dessus du déclencheur (en dessous faute de place), fond presque noir (`gray-950`).
  Toute notification passagère passe par un toast : `Alpine.store('toasts').show({ message, type,
  action, duration, key, position })` (`resources/js/toasts.js`, règles pures dans
  `toast-rules.js`, testées par Node), ou par le flash de session `toast`
  (`['type' => …, 'message' => …]`, ou le message seul), que la région affiche au chargement et,
  sans JavaScript, dans un `<noscript>`. Types, icônes et rôles ARIA de `<x-alert>` (`info` par
  défaut). `action` (`{ label, run }`) ajoute un bouton (« Annuler »…), qui lance `run()` puis
  ferme le toast ; `key` remplace le toast de même clef ; `position` choisit l'une des six places
  (`top-left` … `bottom-right`), celle de la région par défaut (`<x-toast-region position="…">`,
  `App\View\Components\ToastRegion`, posée une fois par `layouts/app`, `bottom-right`). Les toasts
  s'empilent sans limite, le plus récent au bord, la pile défilant au-delà de l'écran. Durée : 5 s, 8 s avec une action, sans fin pour
  `danger` (`duration`, 0 sans fin) ; la minuterie s'arrête au survol, au focus et onglet caché, un
  filet montre le temps restant. Bande sombre (`gray-950`) comme `<x-tooltip>`, dans un popover
  manuel remonté à chaque toast. Un toast ne prend jamais le focus ; Échap ferme celui qui l'a.
  Toute icône vient de Lucide, par `mallardduck/blade-lucide-icons` (sur `blade-ui-kit/blade-icons`) :
  `<x-lucide-circle-help class="size-4" aria-hidden="true"/>`, ou `<x-dynamic-component>` pour un nom
  calculé ; noms sur https://lucide.dev/icons. Une entrée de menu déroulant prend la sienne par
  `<x-dropdown-link icon="log-out">` ; les entrées simples du menu utilisateur en ont toutes une.
  `<x-avatar :user size="sm|md|lg">` représente un utilisateur par une pastille ronde, sa photo
  (`User::avatarUrl()`) ou ses initiales (`User::initials()`), masquée aux lecteurs d'écran : le nom
  est toujours écrit à côté.
  `<x-switch label="…">` (`App\View\Components\ChoiceSwitch`, enregistré sous ce nom, Switch étant
  réservé en PHP) est un interrupteur de 2 ou 3 options en radios natifs, au curseur glissant en CSS ;
  `variant="color-scheme"` donne clair / système / sombre, `model` le lie à Alpine. Pas de SVG copié dans les vues, hormis le logo
  (`x-application-logo`). La taille passe par `size-*` et l'épaisseur du trait, 2 par défaut, par une
  classe (`stroke-1`, `stroke-[1.25]`) : un attribut `stroke-width` sortirait en double sur le `<svg>`.
  Tout choix de mois passe par `<x-month-picker>` (`App\View\Components\MonthPicker`, composant
  Alpine `monthPicker` de `resources/js/month-picker.js`), inspiré de `<input type="month">`, que
  Safari et Firefox sur ordinateur n'affichent qu'en champ texte : un déclencheur à l'allure d'un
  champ, et un popover « une année, douze mois » placé comme `<x-tooltip>`. Trois modes : `single`
  (« 2026-11 », champ `name`), `multiple` (liste, champs `name[]`) et `range` (`start` / `end`,
  champs `name[start]` / `name[end]`). En mode `range`, le déclencheur est plus large (`w-80`, la
  plus longue plage y tient) et le popover a deux panneaux, « Début » et « Fin », chacun avec son
  année : un mois choisi dans « Début » passe le focus à « Fin », qui grise les mois antérieurs, et
  un mois choisi dans « Fin » applique la plage. Saisie libre par défaut, ou restreinte par
  `:restriction` (`App\Support\MonthRestriction`) : bornes `min` / `max`, liste blanche `allow` et
  liste noire `deny`, combinables, de mois (« 2026-11 ») et de plages en intervalle ISO 8601
  (« 2025-01/2025-06 ») ; en mode `range`, seules les bornes de la plage doivent être autorisées.
  La restriction, construite par le serveur, est la seule source de vérité : le composant en envoie
  une copie au navigateur, qui ne sert qu'à griser les mois interdits, et la règle
  `App\Rules\MonthSelection`, avec la même restriction, contrôle la valeur reçue (détails dans le
  docblock de `MonthPicker`). La règle et le libellé sont écrits en PHP et en JavaScript
  (`resources/js/month-picker-rules.js`), testés sur les mêmes cas. La classe normalise la valeur
  initiale et calcule le libellé initial ; le parent lie la sélection par `x-model`
  (`x-modelable`). Clavier : flèches entre mois choisissables, Page préc. / suiv. pour l'année,
  Début / Fin, Échap.
  Tout choix de jour passe par `<x-date-picker>` (`App\View\Components\DatePicker`, composant Alpine
  `datePicker`), construit comme `<x-month-picker>` : mêmes modes (`single`, `multiple`, `range`, une
  plage d'un jour ayant son début égal à sa fin), même popover, un panneau par mois en semaines à
  partir du lundi, restriction `App\Support\DateRestriction` (jours « 2026-11-09 », plages
  « 2026-01-01/2026-01-31 ») contrôlée par la règle `App\Rules\DateSelection`, règles et libellés
  (« 1 – 30 nov. 2026 ») écrits en PHP et en JavaScript (`resources/js/date-picker-rules.js`) sur les
  mêmes cas. En mode `range`, `:presets` propose des plages toutes faites (`App\Enums\DatePreset`),
  calculées par le serveur et ramenées dans les bornes de la restriction. Clavier : flèches (jour,
  semaine), Début / Fin (semaine), Page préc. / suiv. (mois, année avec Maj), Échap.
  Toute sélection de plusieurs options passe par `<x-multi-select name="…" label="…" :options="…">`
  (`App\View\Components\MultiSelect`, composant Alpine `multiSelect`) : un déclencheur qui résume la
  sélection (« 3 comptes », par `noun`) et un popover de cases à cocher natives, envoyées dans
  `name[]` sans JavaScript, avec un champ de recherche au-delà de 8 options. Une option peut porter
  une icône (`icon`, nom Lucide) et sa couleur (`iconClass`), affichées devant son libellé. Son
  identifiant vient de `name` : un nom par page. `:multiple="false"` en fait un choix unique, au
  même déclencheur et au même popover : des radios natifs envoyés dans `name`, précédés d'un radio
  vide libellé par le placeholder (« Tout »), la valeur liée en texte (`''` sans choix) ; un clic de
  souris ou Entrée choisit et ferme, les flèches changent le choix sans fermer. « Sens » et
  « Pointage » du Grand livre l'utilisent.
  Toute colonne triable d'un tableau passe par `<x-sortable-heading column="…" :sort :direction>`
  (`App\View\Components\SortableHeading`) : un `<th>` à `aria-sort` dont le lien pose `sort` et `dir`
  dans l'adresse courante, en gardant ses autres paramètres ; le contrôleur valide les deux valeurs.
  Toute navigation entre pages sœurs passe par `<x-segmented-nav label="…">` (contrôle segmenté :
  bande grise, page courante en relief), chaque lien par `<x-segmented-nav.item :href="…" :current="…">`,
  qui porte `aria-current="page"` sur la page courante ; `<x-portfolio-nav>` en est l'usage pour les
  pages d'un portfolio.
  Toute navigation entre les sections d'une page passe par `<x-side-nav label="…">` : une colonne
  collante sur grand écran, une bande horizontale défilante et collante sur écran étroit. Chaque lien
  `<x-side-nav.item href="#…" status="…" :count="…">` porte une pastille d'état (`ok`, `warning`,
  `danger`, doublée d'un texte pour les lecteurs d'écran) et un nombre facultatifs. Le composant
  Alpine `sectionSpy` (`resources/js/section-spy.js`), posé sur la racine, met `aria-current="true"`
  sur le lien de la section en lecture (filet indigo) et le centre dans la bande.
  `<x-meter :value="0.42" level="…">` dessine une jauge fine, colorée selon le niveau, qui double une
  mesure écrite (`aria-hidden`).
  Tout bouton à deux états passe par `<x-toggle-button name="…" :pressed="…">` (bouton poussoir) :
  un bouton d'envoi qui porte `aria-pressed` et envoie l'état opposé (`name=1` pour l'enfoncer,
  `name=0` pour le relâcher), dans un formulaire GET, sans JavaScript. Relâché, il a l'allure blanche
  des boutons de pagination ; enfoncé, il passe en indigo plein avec une ombre intérieure, par les
  variantes `aria-pressed:`.
  Toute carte passe par `<x-card>`, en trois parties séparées par un filet : l'en-tête (slots `title` /
  `description`), le corps (slot par défaut, omis s'il est vide) et le pied (slot `footer`, sur fond
  teinté, actions alignées à droite, « Annuler » avant le bouton principal). Le formulaire reste dans le
  corps, sous un `id`, et le bouton du pied s'y rattache par l'attribut `form` ; une carte d'archivage
  n'a pas de corps, son formulaire tient dans le pied. `danger` ajoute l'anneau et le titre rouges des
  cartes d'archivage et de suppression. Pagination par la vue `pagination.narrow`.
- Aucun champ ne porte `autofocus` par défaut : il s'ajoute au cas par cas, quand un formulaire le
  demande.
- Toute confirmation passe par `<x-dialog id="…">`, sans `confirm()` JavaScript : un `<dialog>` natif
  mis en page comme `<x-card>` (mêmes slots, `danger`). Le bouton de la carte l'ouvre sans script par
  l'API Invoker Commands (`type="button" commandfor="<id>" command="show-modal"`), « Annuler » le ferme
  (`command="close"`), comme Échap ou un clic hors de la boîte (`closedby="any"`). Le formulaire de
  l'action est dans le pied de la boîte ; `show` l'ouvre au chargement (erreurs de validation, par
  Alpine).
- Tailwind CSS 4 se configure dans `resources/css/app.css`, sans fichier de configuration JavaScript, et
  passe par son plugin Vite. Ce fichier définit les couleurs sémantiques (`primary` indigo, `secondary`
  gris, `success`, `danger`, `warning`, `info`) comme alias de la palette, dans `@theme inline`, et un
  style de base qui garde le curseur des boutons de Tailwind 3, et les barres de défilement (fines, sans
  flèches, aux couleurs du thème : pseudo-éléments `::-webkit-scrollbar` pour Chromium et Safari,
  `scrollbar-width` / `scrollbar-color` pour Firefox seulement, Chromium ignorant les premiers dès que
  les seconds sont posés). Tailwind trouve seul les classes du
  projet (vues, `app/`), sans `@source`.
- Les classes suivent les consignes Tailwind 4 de Boost : toute bordure porte sa couleur (clair et
  sombre), l'espacement entre éléments voisins passe par `gap` (pas de `space-x` / `space-y`), pas de
  classe inexistante ni redondante.
- Espacement : le conteneur porte le `gap`, ses enfants n'ont aucune marge. `<main>` porte le padding
  de la page (`py-6`) ; chaque page range son contenu dans un conteneur unique
  (`max-w-* mx-auto flex flex-col gap-6`) ; un champ groupe label, saisie et erreur en
  `flex flex-col gap-1`. Seuls les décalages de positionnement gardent une marge (panneau absolu de
  `x-dropdown`, centrage `m-auto` de `x-dialog`).
- Thème clair / sombre / système (`App\Enums\ColorScheme`), choisi par l'interrupteur de la barre du
  haut (store Alpine `theme`, `resources/js/theme.js`) et enregistré sur le profil (`users.color_scheme`,
  `PUT /profile/color-scheme`, hors de `password.confirm`, réponse 204 au script). Pour un utilisateur
  connecté, `layouts/empty` pose sur `<html>` la classe `light` ou `dark` et `data-color-scheme` ; le
  script en ligne recopie ce choix dans le `localStorage` (clef `theme`, absente pour « système »),
  que les pages d'invité appliquent avant le premier rendu. `resources/css/app.css` redéfinit la variante `dark:`
  (`@custom-variant`) : la classe `dark` l'active, la classe `light` l'écarte, et sans classe le système
  décide (`prefers-color-scheme`) ; chaque règle `dark:` sort donc en double dans le CSS. `<html>` porte
  `scheme-light dark:scheme-dark` pour les contrôles natifs. Les pages d'erreur viennent de Laravel,
  sauf `501` propre au projet, et passent par `resources/views/errors/minimal.blade.php`, dans la mise
  en page invité : elles suivent donc le thème choisi. Si leur rendu échoue (manifeste Vite absent…),
  Laravel répond hors débogage par sa page brute de secours. Toute vue nouvelle ou modifiée porte ses
  variantes `dark:`, sur la palette en place : fond de page `gray-900`, surfaces `gray-800`, en-têtes
  de tableau `gray-700`, texte `gray-300`, accents `indigo-400`. En sombre, aucun bouton n'est clair :
  le principal (`x-primary-button`, `x-link.primary`) passe en indigo plein (`indigo-500`, plus clair
  au survol), le secondaire en surface grise surélevée (`gray-700`, bordure `gray-600`), le danger
  reste rouge.

### Mails

- `App\Observers\PortfolioObserver` (enregistré dans `Portfolio::booted()`) envoie `PortfolioCreatedMail`
  et `PortfolioDeletedMail` avec deux minutes de délai (`later()`) à l'administrateur
  (`config('app.admin.email')`, variable `APP_ADMIN_EMAIL`), et n'envoie rien si l'adresse est vide. Les
  mailables implémentent `ShouldQueue`.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
