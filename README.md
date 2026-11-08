# Portfolios

Application web de suivi de comptes, regroupés en portfolios.

## Présentation

Chaque utilisateur tient ses propres portfolios. Un portfolio regroupe des comptes (bancaires, d'épargne,
de titres…) ; un compte enregistre des opérations, c'est-à-dire des montants datés, dont l'application
calcule le solde et le cumul au fil du temps.

- **Comptes** : solde, nombre d'opérations, première et dernière date. Un compte qui ne sert plus
  s'archive : ses opérations restent consultables en lecture seule, et il peut être désarchivé.
- **Opérations** : saisie, modification et suppression, avec le solde cumulé du compte à chaque ligne.
- **Analytique** : chaque portfolio définit ses axes (par exemple « Logement », « Loisirs »), et une
  opération peut se répartir entre plusieurs axes, en pourcentages qui totalisent 100 %.
- **Interface** : en français, en thème clair, sombre ou celui du système.

Les données de chaque utilisateur lui sont réservées : un autre utilisateur ne peut ni les voir ni les
modifier. L'inscription est ouverte depuis la page de connexion. L'administrateur reçoit un mail à
chaque création ou archivage d'un portfolio.

Pile : PHP 8.5, Laravel 13, Blade, Alpine.js, Tailwind CSS 4, MySQL.

## Déploiement

### Prérequis

- PHP 8.5 avec les extensions `intl`, `pdo_mysql` et `mbstring` (OPcache recommandé), et Composer.
- MySQL 8.0 ou plus récent : les vues de l'application utilisent des fonctions de fenêtrage et la
  collation `utf8mb4_0900_ai_ci`.
- Node.js 20.19 ou 22.12 au moins, pour construire les fichiers front (Vite 8).
- Un serveur SMTP pour les mails (réinitialisation du mot de passe, mails à l'administrateur).
- Un serveur web (Nginx, Apache…) dont la racine est le dossier `public/` de l'application.

### Première installation

1. Récupérer le code et installer les dépendances :

   ```sh
   git clone https://github.com/sfaut/portfolios.git
   cd portfolios
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

2. Créer la base de données :

   ```sql
   CREATE DATABASE portfolios CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
   ```

3. Copier `.env.example-production` vers `.env`, puis y renseigner au moins `APP_URL`, les accès à la
   base (`DB_*`) et au serveur de mail (`MAIL_*`), et l'administrateur (`APP_ADMIN_*`, à décommenter).
   Générer ensuite la clef de l'application :

   ```sh
   php artisan key:generate
   ```

4. Créer les tables et le compte administrateur :

   ```sh
   php artisan migrate --seed --force
   ```

   Une fois l'administrateur créé, `APP_ADMIN_PASSWORD` peut quitter le `.env`. `APP_ADMIN_EMAIL` y
   reste : c'est l'adresse qui reçoit les mails de portfolio, qui ne partent pas sans elle.

5. Mettre en cache la configuration, les routes, les vues et les icônes :

   ```sh
   php artisan optimize
   ```

6. Lancer en permanence un worker de file, par exemple sous systemd ou Supervisor : les mails passent
   par la file `database`.

   ```sh
   php artisan queue:work
   ```

Les dossiers `storage/` et `bootstrap/cache/` doivent être accessibles en écriture au serveur web.

### Mise à jour

```sh
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

`queue:restart` demande au worker de s'arrêter après sa tâche en cours ; systemd ou Supervisor le
relance alors sur le nouveau code.

### Vérification du serveur

Le workflow GitHub Actions « Vérification du runner » (`.github/workflows/check-runner.yaml`), lancé à
la main, affiche depuis le runner auto-hébergé de production la version de PHP, les extensions
attendues et la place disque.

## Développement

### Installation

En plus des prérequis du déploiement, le développement demande Node.js 22 au moins.

```sh
composer install
npm install
cp .env.example-local .env
php artisan key:generate
```

Créer les bases de développement et de test, puis renseigner dans `.env` les identifiants MySQL et
l'administrateur (`APP_ADMIN_*`) :

```sql
CREATE DATABASE portfolios_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE portfolios_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
```

```sh
php artisan migrate --seed
```

### Lancement

```sh
composer dev
```

Cette commande lance ensemble le serveur PHP, le worker de file, le suivi des journaux (Pail) et le
serveur Vite. L'application répond sur http://portfolios.localhost:10000 : les navigateurs résolvent
`*.localhost` vers la machine locale, sans modifier le fichier `hosts`. Si le port 10000 est déjà
occupé, `php artisan serve` passe au suivant sans prévenir, et les liens générés par l'application
visent alors le mauvais port.

### Tests et style

```sh
php artisan test
vendor/bin/pint --test
```

Les tests utilisent la base `portfolios_test`, vidée à chaque lancement, et demandent le manifeste Vite
(`npm run build`). Le code suit le format de Pint (preset `laravel`) : `vendor/bin/pint --dirty`
formate les fichiers modifiés.

Les conventions du projet et son architecture sont décrites dans [CLAUDE.md](CLAUDE.md).

## Licence

Portfolios est distribué sous [licence MIT](LICENSE).
