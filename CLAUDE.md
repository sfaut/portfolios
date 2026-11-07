# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projet

Portfolios (dépôt et paquet Composer `sfaut/portfolios`) : gestionnaire de portefeuilles et de comptes.
Chaque utilisateur possède des portfolios, qui regroupent des comptes, qui regroupent des opérations
(montants datés).

Pile : PHP 8.4+, Laravel 13, Blade + Alpine.js + Tailwind CSS 3 (base Breeze), Pest 5, MySQL, Vite.
Interface en français (`APP_LOCALE=fr`, fuseau `Europe/Paris`, montants en euros via
`Number::currency(…, 'EUR', 'fr')`, qui demande l'extension `intl`).

## Commandes

- Installation : `composer install`, `npm install`, copier `.env.example-local` (ou
  `.env.example-production`) vers `.env` et y renseigner les identifiants, puis `php artisan key:generate`
  et `php artisan migrate --seed`. Le seeder crée l'administrateur (id 1) depuis les variables
  `APP_ADMIN_*`, en commentaire dans les modèles. Une variable présente mais vide l'emporte sur la valeur
  par défaut du seeder (`env()` renvoie `''`) : la laisser en commentaire ou la renseigner.
- Développement : `composer dev` lance ensemble `php artisan serve`, `php artisan queue:listen`,
  `php artisan pail` et `npm run dev`. Le worker de file est nécessaire : les mails de portfolio passent
  par la file `database`.
- Build front : `npm run build`.
- Style : Pint, sans `pint.json` (preset `laravel`), voir « Conventions » ci-dessous.
- Tests : `php artisan test`, un fichier `vendor/bin/pest tests/Feature/ProfileTest.php`, un test
  `php artisan test --filter="profile page is displayed"`.
- GitHub Actions : `.github/workflows/check-runner.yaml`, lancé à la main, vérifie le runner auto-hébergé
  de production (PHP et ses extensions, disque de `/var/www`).

### Tests

- Les tests tournent sur la base du `.env` : `portfolios_dev` en local, qui sert de base de développement
  et de test. `tests/Pest.php` applique `RefreshDatabase` à `tests/Feature`, donc chaque lancement vide
  cette base ; `php artisan db:seed` recrée ensuite l'administrateur. Pour lancer les tests sans toucher à
  ses données, créer une base vide et la passer par le shell (`DB_DATABASE=<base> php artisan test`), une
  variable d'environnement l'emportant sur le `.env`.
- Les tests demandent MySQL, à cause des vues créées par les migrations (`CREATE OR REPLACE VIEW`,
  fonctions de fenêtrage), et le manifeste Vite (`npm run build`) pour les vues Blade.
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
  `account`), qui exige un parent visible, archivé ou non. La liaison de modèle des routes renvoie donc
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
  Noms de route : `portfolio.*`, `account.*`, `operation.*`, `profile.*`. La page d'accueil après
  connexion est `portfolio.index` : le projet n'a pas la route `dashboard` du code Breeze d'origine.
  La suppression de compte utilisateur (`ProfileController::destroy()`) est désactivée et répond 501.
- Par entité : un contrôleur, une FormRequest partagée par `store` et `update` (avec `attributes()` en
  français), des vues `resources/views/<entité>/{index,show,create,edit}.blade.php` et un formulaire commun
  `partials/form.blade.php`.
- `AccountController::show()` redirige vers la dernière page des opérations quand `page` est absent, en
  refaisant le flash de session (`reflash()`) pour garder le message.
- Messages flash : la clef varie selon la vue qui l'affiche, `message` (vues portfolio), `alert`
  (`account/show`), `status` (vues Breeze). Un flash n'apparaît que si la vue de destination lit sa clef.
  Les messages de portfolio sont dans `lang/fr/portfolio.php` ; les autres sont écrits en dur dans les
  contrôleurs.
- Mise en page : `<x-app-layout>` → `layouts/app.blade.php` → `<x-empty-layout>` (HTML, `@vite`). Les
  composants sont surtout anonymes (`resources/views/components`, dont `x-alert type="…"`,
  `x-link.primary` / `x-link.secondary` avec `:target`). Pagination par la vue `pagination.narrow`.
- `tailwind.config.js` définit des couleurs sémantiques (`primary` indigo, `secondary`, `success`,
  `danger`, `warning`, `info`), utilisées par les composants récents.

### Mails

- `App\Observers\PortfolioObserver` (enregistré dans `Portfolio::booted()`) envoie `PortfolioCreatedMail`
  et `PortfolioDeletedMail` avec deux minutes de délai (`later()`) à une adresse écrite en dur. Les
  mailables implémentent `ShouldQueue`. `SendPortfolioCreatedMailJob` et `MailingController` existent sans
  être branchés à une route.
