# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projet

Portfolios (dépôt et paquet Composer `sfaut/portfolios`) : gestionnaire de portefeuilles et de comptes.
Chaque utilisateur possède des portfolios, qui regroupent des comptes, qui regroupent des opérations
(montants datés).

Pile : PHP 8.5+, Laravel 13, Blade + Alpine.js + Tailwind CSS 4 (base Breeze), Pest 5, MySQL, Vite 8.
Interface en français (`APP_LOCALE=fr`, fuseau `Europe/Paris`), extension `intl` requise. Tout montant
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
  `php artisan test --filter="profile page is displayed"`.
- GitHub Actions : `.github/workflows/ci.yaml` vérifie le code (Pint, build du front, tests sur un
  service MySQL) à chaque push sur `master` et à chaque pull request. À la publication d'une release, il
  construit en plus l'archive de déploiement (`composer install --no-dev`, `npm run build`), qu'il
  ajoute à la release avec son empreinte SHA-256 (`portfolios-vX.Y.Z.tar.gz`, `.sha256`).
- Production : le déploiement est décrit dans `README.md`, seule source du script `deploy.sh` dans le
  dépôt (section « Script de déploiement »). Lancé à la main sur le serveur (`deploy.sh vX.Y.Z`), il installe
  l'archive de la release dans `/var/www/portfolios/releases/vX.Y.Z`, la relie à `shared/.env` et
  `shared/storage`, lance `optimize`, sauvegarde la base (`php artisan db:dump`, compressée dans `backups/`), lance
  `migrate --force`, bascule le lien `current`, puis redémarre le worker de file, dont il crée le
  service systemd (`portfolios-queue`) s'il n'existe pas. Au premier lancement, il crée `shared/.env`
  depuis `.env.model-prod` de la version, avec une `APP_KEY` générée une fois pour toutes, et s'arrête.
  `optimize`
  met en cache la configuration, les routes, les vues et, par blade-icons, les icônes (`icons:cache`,
  sans quoi chaque rendu relit les SVG sur le disque). Une migration doit rester compatible avec la
  version précédente, qui peut revenir par `deploy.sh` sur le schéma migré.
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
  nouvelle route sur un portfolio, un compte ou une opération s'y ajoute.

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
  les modèles.
- Lecture et écriture passent par des modèles distincts. Les vues SQL `account_reports` (solde, nombre
  d'opérations, première et dernière date) et `operation_reports` (cumul `amount_running_sum` par compte,
  ordonné par `delivery_at`, `id`) sont créées dans la migration
  `database/migrations/2024_12_21_222937_create_operation_table.php` et lues par les modèles en lecture
  seule `AccountReport` / `OperationReport`. L'affichage les utilise (`Portfolio::accountsReport()`,
  `Account::operationsReport()`) ; les écritures passent par `Account` / `Operation`. Changer les colonnes
  de `accounts` ou `operations` demande de recréer ces vues dans une nouvelle migration.
- Analytique : un portfolio a des axes (`AnalyticAxis`, table `analytic_axes`, nom unique dans le
  portfolio, archivés par `SoftDeletes`) ; une ligne analytique (`AnalyticLine`) attribue à un axe une
  part en pourcentage (deux décimales) d'une opération. Les lignes d'une opération sont facultatives ;
  présentes, elles portent des axes distincts et totalisent exactement 100 %, contrôle fait en centièmes
  entiers par `OperationRequest::after()`. Une modification d'opération remplace ses lignes. Un axe
  archivé ne se choisit plus, sauf sur une opération qui l'utilise déjà, et reste affiché sur ses
  lignes (`AnalyticLine::analyticAxis()` inclut les archivés).
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

- Toutes les routes métier sont dans `routes/web.php`, sous le middleware `auth` ; `routes/auth.php`
  (Breeze) y est inclus. Seule la création est imbriquée (`/portfolio/{portfolio}/account/create`,
  `/account/{account}/operation/create`) ; les autres actions sont à plat (`/account/{account}/edit`…).
  Noms de route : `portfolio.*`, `account.*`, `operation.*`, `analytic-axis.*`, `profile.*`. Les axes
  analytiques ont aussi leur liste imbriquée (`/portfolio/{portfolio}/analytic-axes`, liée depuis
  `portfolio/show`), faute de page parent qui l'accueille ; `edit`, `update` et `restore` portent
  `->withTrashed()`, comme pour les comptes. La page d'accueil après
  connexion est `portfolio.index` : le projet n'a pas la route `dashboard` du code Breeze d'origine.
  La suppression de compte utilisateur (`ProfileController::destroy()`) est désactivée et répond 501.
- Par entité : un contrôleur, une FormRequest partagée par `store` et `update` (avec `attributes()` en
  français), des vues `resources/views/<entité>/{index,show,create,edit}.blade.php` et un formulaire commun
  `partials/form.blade.php`.
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
  le portfolio a des opérations (`Portfolio::operationMonths()`) ; un autre mois est ignoré.
  Le composant Alpine `accountSearch` (`resources/js/account-search.js`) interroge la même route après
  300 ms de pause (aussitôt pour le mois), avec `X-Requested-With` : `PortfolioController::show()` ne renvoie alors que le
  fragment Blade `accounts` (`fragmentIf()`, en-tête `Vary`), qui remplace les résultats. Le résumé
  (« 2 comptes sur 14 ») se place à droite des filtres, sans changer la hauteur de la barre ; sans
  JavaScript, le formulaire recharge la page filtrée.
- Répartition analytique du formulaire d'opération : composant Alpine `analyticLines`
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
- `AccountController::show()` redirige vers la dernière page des opérations quand `page` est absent, en
  refaisant le flash de session (`reflash()`) pour garder le message.
- Messages flash : la clef varie selon la vue qui l'affiche, `message` (vues portfolio), `alert`
  (`account/show`), `status` (vues Breeze). Un flash n'apparaît que si la vue de destination lit sa clef.
  Les messages de portfolio sont dans `lang/fr/portfolio.php` ; les autres sont écrits en dur dans les
  contrôleurs.
- Mise en page : `<x-app-layout>` → `layouts/app.blade.php` → `<x-empty-layout>` (HTML, `@vite`). Les
  composants sont surtout anonymes (`resources/views/components`, dont `x-link.primary` /
  `x-link.secondary` avec `:target`, `x-text-input` / `x-textarea` pour les champs). `x-alert type="…"`
  (`info` par défaut, `success`, `warning`, `danger`) tient de sa classe `App\View\Components\Alert` ses
  couleurs, son icône Lucide et son rôle ARIA (`alert` pour `danger`, `status` sinon) ; il garde les
  retours à la ligne du message, sans ses blancs de début et de fin.
  Toute infobulle passe par `<x-tooltip>` (slot par défaut : déclencheur, slot `content` : contenu),
  sans attribut `title` : popover manuel ouvert par Alpine au survol et au focus, dans la couche
  supérieure (il échappe à un parent en `overflow-hidden`), placé par le positionnement d'ancre CSS
  au-dessus du déclencheur (en dessous faute de place), fond presque noir (`gray-950`).
  Toute icône vient de Lucide, par `mallardduck/blade-lucide-icons` (sur `blade-ui-kit/blade-icons`) :
  `<x-lucide-circle-help class="size-4" aria-hidden="true"/>`, ou `<x-dynamic-component>` pour un nom
  calculé ; noms sur https://lucide.dev/icons. Pas de SVG copié dans les vues, hormis le logo
  (`x-application-logo`). La taille passe par `size-*` et l'épaisseur du trait, 2 par défaut, par une
  classe (`stroke-1`, `stroke-[1.25]`) : un attribut `stroke-width` sortirait en double sur le `<svg>`.
  Tout choix de mois passe par `<x-month-picker>` (`App\View\Components\MonthPicker`, composant
  Alpine `monthPicker` de `resources/js/month-picker.js`), inspiré de `<input type="month">`, que
  Safari et Firefox sur ordinateur n'affichent qu'en champ texte : un déclencheur à l'allure d'un
  champ, et un popover « une année, douze mois » placé comme `<x-tooltip>`. Trois modes : `single`
  (« 2026-11 », champ `name`), `multiple` (liste, champs `name[]`) et `range` (`start` / `end`,
  champs `name[start]` / `name[end]`) ; `min` / `max` bornent les mois, `months` les restreint. La
  classe normalise la valeur et calcule le libellé initial, que le JavaScript recalcule à
  l'identique ; le parent lie la sélection par `x-model` (`x-modelable`). Clavier : flèches entre mois
  choisissables, Page préc. / suiv. pour l'année, Début / Fin, Échap.
  Toute carte passe par `<x-card>`, en trois parties séparées par un filet : l'en-tête (slots `title` /
  `description`), le corps (slot par défaut, omis s'il est vide) et le pied (slot `footer`, sur fond
  teinté, actions alignées à droite, « Annuler » avant le bouton principal). Le formulaire reste dans le
  corps, sous un `id`, et le bouton du pied s'y rattache par l'attribut `form` ; une carte d'archivage
  n'a pas de corps, son formulaire tient dans le pied. `danger` ajoute l'anneau et le titre rouges des
  cartes d'archivage et de suppression. Pagination par la vue `pagination.narrow`.
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
- Thème clair / sombre / système, choisi dans le menu utilisateur (store Alpine `theme`,
  `resources/js/theme.js`) et gardé par navigateur dans le `localStorage` (clef `theme`, absente pour
  « système »). Le choix devient la classe `light` ou `dark` de `<html>`, posée avant le premier rendu
  par le script en ligne de `layouts/empty`. `resources/css/app.css` redéfinit la variante `dark:`
  (`@custom-variant`) : la classe `dark` l'active, la classe `light` l'écarte, et sans classe le système
  décide (`prefers-color-scheme`) ; chaque règle `dark:` sort donc en double dans le CSS. `<html>` porte
  `scheme-light dark:scheme-dark` pour les contrôles natifs. Les pages d'erreur viennent de Laravel,
  sauf `501` propre au projet, et passent par `resources/views/errors/minimal.blade.php`, dans la mise
  en page invité : elles suivent donc le thème choisi. Si leur rendu échoue (manifeste Vite absent…),
  Laravel répond hors débogage par sa page brute de secours. Toute vue nouvelle ou modifiée porte ses
  variantes `dark:`, sur la palette en place : fond de page `gray-900`, surfaces `gray-800`, en-têtes
  de tableau `gray-700`, texte `gray-300`, accents `indigo-400`.

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
