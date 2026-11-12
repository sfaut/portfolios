# Portfolios

[![CI](https://img.shields.io/github/actions/workflow/status/sfaut/portfolios/ci.yaml?branch=master&label=CI)](https://github.com/sfaut/portfolios/actions/workflows/ci.yaml)
[![Release](https://img.shields.io/github/v/release/sfaut/portfolios)](https://github.com/sfaut/portfolios/releases/latest)
[![License](https://img.shields.io/github/license/sfaut/portfolios)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-8.5-informational)
![Laravel](https://img.shields.io/badge/Laravel-13-informational)
![Database](https://img.shields.io/badge/database-MySQL%208.0%2B-informational)

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

## Déploiement sur un serveur

Cette section propose une méthode de déploiement, parmi d'autres possibles : chemins, comptes, versions
et outils s'adaptent au serveur.

Chaque release publiée sur GitHub reçoit de la CI une archive de l'application prête à servir (code,
dépendances de production, front construit), avec son empreinte SHA-256. Sur le serveur, le script
`deploy.sh` installe une version à la demande dans son propre dossier, puis bascule vers elle un lien
symbolique, d'un coup :

```
/var/www/portfolios/
├── deploy.sh                 script de déploiement
├── backups/                  sauvegardes de la base, une par déploiement
├── current -> releases/vX.Y.Z
├── releases/
│   ├── vX.Y.Z/               version active
│   └── …                     versions précédentes, pour revenir en arrière
└── shared/
    ├── .env                  configuration, commune à toutes les versions
    └── storage/              journaux, sessions, vues compilées
```

Le serveur se passe ainsi de Git, de Composer et de Node.js. Les archives existent à partir de la
version qui suit v1.1.1.

### Prérequis

- PHP 8.5, en ligne de commande et en FPM, avec les extensions `intl`, `pdo_mysql`, `mbstring`,
  `zip`, `gd` (avec WebP, et AVIF pour accepter ce format) et `exif` (OPcache recommandé). Sur Debian
  ou Ubuntu, les paquets `php8.5-fpm`, `php8.5-cli`, `php8.5-intl`, `php8.5-mysql`,
  `php8.5-mbstring`, `php8.5-zip` et `php8.5-gd` viennent du dépôt d'Ondřej Surý (`exif` est compris
  dans `php8.5-common`).
- Pour les photos de profil, PHP-FPM accepte un envoi de 8 Mo : `upload_max_filesize = 8M` et
  `post_max_size = 9M` dans `/etc/php/8.5/fpm/conf.d/90-portfolios.ini`, puis
  `systemctl restart php8.5-fpm`. Le navigateur recadre la photo et n'envoie que 512 pixels de côté ;
  la photo d'origine ne part que sans JavaScript.
- MySQL 8.0 ou plus récent : les vues de l'application utilisent des fonctions de fenêtrage et la
  collation `utf8mb4_0900_ai_ci`.
- Nginx, `curl`, systemd et `mysqldump` (client MySQL, installé avec le serveur MySQL), dont se sert
  la sauvegarde de la base.
- Un serveur SMTP pour les mails (réinitialisation du mot de passe, mails à l'administrateur).

Les commandes qui suivent se lancent en root ; PHP-FPM et Nginx tournent sous le compte `www-data`.
Dans les exemples, `vX.Y.Z` désigne la version à installer.

### Première installation

1. Créer la base de données et son utilisateur :

   ```sql
   CREATE DATABASE portfolios CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
   CREATE USER 'portfolios'@'localhost' IDENTIFIED BY '<mot de passe>';
   GRANT ALL PRIVILEGES ON portfolios.* TO 'portfolios'@'localhost';
   GRANT SHOW_ROUTINE ON *.* TO 'portfolios'@'localhost';
   ```

   `SHOW_ROUTINE` (MySQL 8.0.20 ou plus récent) permet à la sauvegarde de `deploy.sh` de copier les
   procédures et fonctions de la base créées par un autre compte ; sans lui, la sauvegarde échoue et
   le déploiement s'arrête.

2. Créer le dossier de l'application, puis y installer `deploy.sh` comme l'indique la section
   « Script de déploiement » ci-dessous :

   ```sh
   mkdir --parents /var/www/portfolios
   chown root:root /var/www/portfolios
   chmod 755 /var/www/portfolios
   ```

   Le script tourne en root : un compte qui pourrait le modifier, ou modifier un dossier qui le
   contient (`/var/www/portfolios`, `/var/www`), lui ferait exécuter n'importe quoi en root (section
   « Propriétaires et droits »).

3. Lancer le script une première fois :

   ```sh
   /var/www/portfolios/deploy.sh vX.Y.Z
   ```

   Faute de configuration, il crée `shared/.env` depuis le modèle de production de la version
   (`.env.model-prod`), y génère la clef de l'application (`APP_KEY`), le rend lisible par le seul
   serveur web, puis s'arrête. Y renseigner au moins `APP_URL`, les accès à la base (`DB_*`) et au
   serveur de mail (`MAIL_*`), et l'administrateur (`APP_ADMIN_*`, obligatoires).

4. Relancer le script, qui déploie cette fois la version, puis créer le compte administrateur :

   ```sh
   /var/www/portfolios/deploy.sh vX.Y.Z
   runuser --user www-data -- php /var/www/portfolios/current/artisan db:seed --force
   ```

   Le seed vérifie les quatre variables `APP_ADMIN_*` : il s'arrête, en les nommant, si l'une manque ou
   n'est pas valide (adresse, mot de passe de 8 caractères au moins). Une fois l'administrateur créé,
   `APP_ADMIN_PASSWORD` peut être vidée. `APP_ADMIN_EMAIL` reste : c'est l'adresse qui reçoit les mails
   de portfolio, qui ne partent pas sans elle. Relancé sur une base qui a déjà un administrateur, le
   seed la laisse telle quelle et le signale.

5. Installer le certificat, puis Nginx, décrits dans les sections suivantes. Le worker de file, lui,
   est déjà en place : `deploy.sh` a créé et démarré son service (section « Worker de file »).

### Installation sur une base existante

Une base qui contient déjà les données d'une installation précédente de Portfolios se reprend telle
quelle, en suivant la première installation avec ces différences :

- Étape 1 : garder la base, sans la recréer. Seuls l'utilisateur MySQL et ses droits sont à créer, s'ils
  n'existent pas déjà (`SHOW_ROUTINE` compris).
- Étape 3 : dans `shared/.env`, remplacer la clef `APP_KEY` que `deploy.sh` vient de générer par celle
  de l'ancienne installation. La clef appartient à l'installation et la suit d'une version à l'autre ;
  elle chiffre les cookies, dont ceux de session et de « Se souvenir de moi », que les utilisateurs
  gardent ainsi. Les données de la base, elles, ne dépendent pas de la clef. Seule exception, une
  ancienne clef publiée (dépôt, sauvegarde partagée…) : connue de tiers, elle leur permet de forger des
  cookies. On garde alors la clef générée, et chacun se reconnecte une fois.
- Avant l'étape 4 : arrêter l'ancienne installation, son worker de file compris
  (`systemctl disable --now <service>`). Deux workers sur la même table `jobs`, l'un sur l'ancien code,
  se partageraient les mails. Si son service s'appelle déjà `portfolios-queue`, supprimer son fichier
  puis lancer `systemctl daemon-reload` : `deploy.sh` crée le nouveau.
- Étape 4 : `deploy.sh` sauvegarde la base, puis ne passe que les migrations qui manquent, d'après la
  table `migrations` ; la sauvegarde permet de revenir à l'état d'avant. Le seed ne crée rien si la base
  a déjà un administrateur.

### Worker de file

L'application range ses mails dans une file, la table `jobs`, et un worker (`php artisan queue:work`)
les envoie en lisant cette file en permanence : sans lui, les mails restent en attente. Le worker tourne
comme service systemd, `portfolios-queue`, que `deploy.sh` crée à son premier déploiement : systemd le
démarre avec le serveur et le relance s'il s'arrête. Son contenu figure dans le script (étape 7).

À chaque déploiement, `deploy.sh` demande au worker de s'arrêter après sa tâche en cours
(`queue:restart`) : systemd le relance aussitôt, sur la nouvelle version. Un service déjà présent est
laissé tel quel, et peut donc s'adapter à la main (`systemctl edit --full portfolios-queue`) ; le script
avertit seulement s'il est arrêté, ou s'il tourne ailleurs que dans `/var/www/portfolios/current`.

```sh
systemctl status portfolios-queue      # état du worker
journalctl --unit portfolios-queue     # sa sortie
```

### Certificat TLS

Un serveur joignable depuis Internet par un nom de domaine reçoit un certificat Let's Encrypt de
Certbot, qui le renouvellera ensuite seul :

```sh
certbot certonly --nginx -d portfolios.example.com
```

Un serveur accessible seulement depuis le réseau local, par une adresse IP privée (192.168.…), ne peut
pas recevoir de certificat Let's Encrypt. [mkcert](https://github.com/FiloSottile/mkcert) crée alors
une autorité de certification locale et un certificat pour cette adresse, à fixer par une réservation
DHCP sur la box :

```sh
mkcert -install
mkcert 192.168.0.10
```

Les fichiers produits (`192.168.0.10.pem`, `192.168.0.10-key.pem`) se rangent par exemple dans
`/etc/nginx/ssl/`. Le certificat racine (`rootCA.pem`, dans le dossier donné par `mkcert -CAROOT`)
s'installe sur chaque appareil qui ouvre l'application ; sa clef (`rootCA-key.pem`) ne quitte jamais le
serveur. Le certificat dure un peu plus de deux ans, et se renouvelle en relançant `mkcert`.

### Configuration Nginx

Exemple pour un certificat Let's Encrypt : remplacer `portfolios.example.com` par le domaine du
serveur, que `APP_URL` reprend (`APP_URL=https://portfolios.example.com`). Avec mkcert, l'adresse IP
prend la place du domaine, et les fichiers du certificat celle des chemins `ssl_certificate` /
`ssl_certificate_key`.

```nginx
# /etc/nginx/sites-available/portfolios

# HTTP : redirection vers HTTPS.
server {
    listen 80;
    listen [::]:80;
    server_name portfolios.example.com;

    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name portfolios.example.com;
    root /var/www/portfolios/current/public;

    ssl_certificate /etc/letsencrypt/live/portfolios.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/portfolios.example.com/privkey.pem;

    # « always » envoie aussi ces en-têtes avec les réponses d'erreur.
    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    index index.php;
    charset utf-8;

    # Photos de profil : jusqu'à 8 Mo, plus le reste du formulaire (1 Mo par défaut).
    client_max_body_size 9m;

    # Un fichier de public/ est servi tel quel ; toute autre adresse passe par Laravel.
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Fichiers construits par Vite, dont le nom change à chaque build : cache d'un an.
    location /build/ {
        expires 1y;
        access_log off;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # Seul index.php s'exécute. $realpath_root résout le lien current : après une bascule, PHP-FPM
    # reçoit le chemin de la nouvelle version.
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Fichiers cachés (.env, .git…) refusés, hormis .well-known (validation des certificats).
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activer le site, vérifier la configuration, puis la recharger :

```sh
ln --symbolic /etc/nginx/sites-available/portfolios /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

L'en-tête `Strict-Transport-Security` (HSTS) oblige les navigateurs à passer par HTTPS pendant un an
pour ce domaine : le garder seulement une fois HTTPS en place et stable.

### Mise à jour et retour arrière

1. Publier la release sur GitHub. La CI vérifie le code du tag (style, build du front, tests), puis,
   seulement si tout passe, ajoute à la release l'archive et son empreinte
   (`portfolios-vX.Y.Z.tar.gz` et `portfolios-vX.Y.Z.tar.gz.sha256`).
2. Sur le serveur, une fois la CI terminée :

   ```sh
   /var/www/portfolios/deploy.sh vX.Y.Z
   ```

   Une archive présente sur la release garantit donc des vérifications terminées et réussies : CI en
   cours ou en échec, `deploy.sh` ne trouve pas l'archive et s'arrête sans rien changer.

Avant les migrations, `deploy.sh` sauvegarde la base dans `backups/`
(`portfolios.pre-vX.Y.Z.sql.gz`, dix gardées), par la commande
`php artisan db:dump` de l'application, qui passe par `mysqldump`. La copie est complète : tables et
données, vues, procédures et fonctions, déclencheurs, événements, jeu de caractères et collation de la
base. Une sauvegarde en échec ou incomplète arrête le déploiement.

Une sauvegarde déjà présente pour la même version (déploiement relancé, ou redéploiement après un
retour arrière) est gardée, renommée avec la date et l'heure de sa création
(`portfolios.pre-vX.Y.Z.<date>-<heure>.sql.gz`). Le nom simple désigne donc toujours la base d'avant
le dernier déploiement de la version : la copie la plus proche des données en service, celle à
restaurer d'ordinaire. Une copie plus ancienne, restaurée par erreur, effacerait tout ce qui a été
saisi depuis.

Une sauvegarde supprime puis recrée la base : elle rend exactement l'état copié, sans les tables ou
objets créés depuis. Elle se restaure, site en maintenance, par un compte MySQL qui a tous les droits
sur la base (root, ou l'utilisateur `portfolios`) :

```sh
runuser --user www-data -- php /var/www/portfolios/current/artisan down
zcat /var/www/portfolios/backups/portfolios.pre-vX.Y.Z.sql.gz | mysql
runuser --user www-data -- php /var/www/portfolios/current/artisan up
```

Migrations en échec : `deploy.sh` s'arrête avant la bascule, le site reste sur la version précédente,
et le message nomme la sauvegarde faite juste avant. MySQL n'annule pas un changement de schéma
interrompu : la base peut être restée à moitié migrée. Restaurer cette sauvegarde, comme ci-dessus,
avant de corriger puis de relancer le déploiement. La relance sauvegarde alors de nouveau la base
d'avant les migrations, sous le nom simple. Relancer sans restaurer ferait porter le nom simple à une
base à moitié migrée ; la copie d'avant les migrations resterait celle renommée avec sa date.

Pour vérifier une sauvegarde sans toucher à la base en service, la restaurer sous un autre nom, ici
`portfolios_verification`. Le nom de la base figure dans la copie, sur ses lignes `DROP DATABASE`,
`CREATE DATABASE` et `USE` : `sed` le remplace sur ces seules lignes, dans une copie lisible par root
seul, à contrôler avant la restauration.

```sh
# Sous-shell : umask 077 (fichier lisible par root seul) ne vaut que pour cette commande.
(
    umask 077
    zcat /var/www/portfolios/backups/portfolios.pre-vX.Y.Z.sql.gz \
        | sed --expression 's/^\(\/\*!40000 DROP DATABASE IF EXISTS \|CREATE DATABASE .*\|USE \)`portfolios`/\1`portfolios_verification`/' \
        > /root/portfolios_verification.sql
)
# Toutes les lignes affichées doivent nommer portfolios_verification : une ligne restée sur
# portfolios remplacerait la base en service.
grep --extended-regexp '^(/\*!40000 DROP DATABASE|CREATE DATABASE|USE )' /root/portfolios_verification.sql
mysql < /root/portfolios_verification.sql
```

La copie se consulte alors comme toute base (`mysql portfolios_verification`), avec ses vues, ses
procédures et ses déclencheurs ; ses événements planifiés tournent aussi, sur ses propres tables. Une
fois la vérification faite, la supprimer :

```sh
mysql --execute 'DROP DATABASE portfolios_verification'
rm /root/portfolios_verification.sql
```

Pour revenir à une version précédente, relancer `deploy.sh` avec son numéro. Encore sur le disque
(`deploy.sh` garde les cinq dernières), elle est réactivée sans téléchargement. Les migrations restent
en place : l'ancienne version tourne sur le schéma de la nouvelle, ce qui demande des migrations
compatibles avec la version précédente (une colonne s'ajoute d'abord, et ne se supprime ou ne se
renomme qu'une version plus tard).

### Propriétaires et droits

Tout appartient à root, sauf ce que l'application écrit : le `storage/` partagé et le cache de chaque
version, à `www-data`. `deploy.sh` les règle ainsi, `shared/.env` compris, hormis `deploy.sh` lui-même,
installé à la main.

```
/var/www/portfolios/                 root:root          755
├── deploy.sh                        root:root          700   lu et lancé par root seul
├── backups/                         root:root          700   fichiers en 600, lus par root seul
├── current -> releases/vX.Y.Z       root:root          lien
├── releases/                        root:root          755
│   └── vX.Y.Z/                      root:root          755 (dossiers) / 644 (fichiers)
│       ├── bootstrap/cache/         www-data:www-data  755   caches écrits par artisan optimize
│       ├── .env -> shared/.env                         lien
│       └── storage -> shared/storage                   lien
└── shared/                          root:root          755
    ├── .env                         root:www-data      640   écrit par root, lu par www-data
    └── storage/                     www-data:www-data  755 (dossiers) / 644 (fichiers)
```

- Nginx (`www-data`) lit les fichiers de `public/`.
- PHP-FPM et le worker de file (`www-data`) lisent le code et le `.env`, et écrivent dans `storage/` et
  dans le `bootstrap/cache/` de leur version.
- root, par `deploy.sh`, installe les versions, bascule `current` et recharge PHP-FPM ; il lance les
  commandes Artisan sous `www-data`, propriétaire des fichiers qu'elles créent.

Le code reste à root : une faille qui permettrait d'exécuter du code sous `www-data` ne pourrait pas
modifier l'application pour s'y installer durablement. Pour la même raison, ni `deploy.sh`, lancé en
root, ni les dossiers qui le contiennent ne doivent être modifiables par `www-data` ou un autre compte.

Le compte de PHP-FPM se vérifie dans `/etc/php/8.5/fpm/pool.d/www.conf` (`user`, `group`) : il doit
être celui d'`APP_USER` dans `deploy.sh`, que reprend le service du worker (`User=`). Les droits d'un
chemin, dossier par dossier, se lisent avec `namei -l /var/www/portfolios/current/public/index.php`.

### Script de déploiement

Le script est [`bin/deploy.sh`](bin/deploy.sh). Ses paramètres, en tête du fichier, s'adaptent au
serveur : dépôt, dossier de l'application, compte du serveur web, service PHP-FPM, nombre de versions
et de sauvegardes gardées.

Il s'installe depuis le tag d'une version, ce qui garde sur le serveur le script de cette version,
pour root seul :

```sh
curl --fail --silent --show-error --location --output /var/www/portfolios/deploy.sh \
    https://raw.githubusercontent.com/sfaut/portfolios/vX.Y.Z/bin/deploy.sh
chown root:root /var/www/portfolios/deploy.sh
chmod 700 /var/www/portfolios/deploy.sh
```

Le relire avant son premier lancement, et reporter ses paramètres s'ils ont été adaptés. Une version
qui modifie le script le signale dans son changelog : il se réinstalle alors de la même façon, avant de
la déployer. Le script est dans le dépôt à partir de la version qui suit v1.7.0 : celui d'une version
plus récente déploie aussi les précédentes. Il ne vient jamais de l'archive de la release, dont les fichiers appartiennent au serveur
web une fois installés (section « Propriétaires et droits »).

## Déploiement sur Laravel Cloud

Laravel Cloud construit l'application depuis le dépôt et la sert sur son infrastructure : ni serveur
ni `deploy.sh` à entretenir. Un environnement suit la branche `master`. Il ne déploie qu'à la demande,
une version publiée, par [`bin/deploy-laravel-cloud.sh`](bin/deploy-laravel-cloud.sh), lancé depuis
un clone du dépôt.

### Configuration de l'environnement

Dans Laravel Cloud, une application liée au dépôt GitHub, et un environnement sur `master` :

- Runtime : PHP 8.5. Les extensions de l'application (`intl`, `zip`, `gd`, `exif`…) font partie de
  l'image.
- Deployments :
  - « Push to deploy » désactivé : un push sur `master` ne déploie rien ;
  - deploy hook activé, son adresse copiée dans le `.env` du clone (`LARAVEL_CLOUD_DEPLOY_HOOK`) ;
  - build commands :

    ```sh
    composer install --no-dev --optimize-autoloader --no-interaction
    npm ci --audit false
    npm run build
    php artisan optimize
    ```

  - deploy command : `php artisan migrate --force`.
- Base de données : un cluster Laravel MySQL (8.4), attaché à l'environnement, qui en reçoit les accès
  (`DB_*`). Les migrations y créent les vues (`account_reports`, `operation_reports`).
- Stockage : un bucket **privé**, attaché sous le nom de disque `avatars`, sans en faire le disque par
  défaut. Il reçoit les photos de profil (`AVATAR_DISK=avatars`) : le système de fichiers de
  l'environnement est effacé à chaque déploiement.
- Worker de file : un processus `queue:work` (« Background processes »), sur la connexion `database`.
  Les mails à l'administrateur partent par la file : l'environnement ne doit pas se mettre en veille
  (hibernation désactivée), sans quoi les tâches attendent son réveil.
- Variables d'environnement : celles de `shared/.env` du serveur, en reprenant sa clef `APP_KEY`
  (sessions et liens envoyés restent valables), avec `AVATAR_DISK=avatars`. Ne pas reprendre :
  - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` : Laravel Cloud les fournit, et
    une variable saisie l'emporte sur la sienne ;
  - `LOG_CHANNEL` et `LOG_STACK` : les journaux iraient dans un fichier effacé au déploiement, au lieu
    de l'onglet des journaux de Laravel Cloud.
- Domaine : ajouté dans les réglages réseau, avec les enregistrements DNS demandés (vérification,
  certificat, puis origine) ; `APP_URL` le reprend.

### Déploiement d'une version

1. Publier la release sur GitHub, et attendre que la CI y ajoute l'archive : c'est la preuve que ses
   vérifications sont passées.
2. Dans Laravel Cloud, lancer une sauvegarde manuelle de la base si la version a des migrations.
3. Depuis le clone :

   ```sh
   bin/deploy-laravel-cloud.sh vX.Y.Z
   ```

   Le script vérifie que le commit du tag est sur `origin/master` et que la release a son archive,
   demande confirmation, puis appelle le deploy hook avec ce commit. Le déploiement se suit dans
   Laravel Cloud.

Retour arrière : relancer le script avec une version précédente. Les migrations restent en place, comme
sur un serveur. Une sauvegarde de la base se restaure dans un nouveau cluster, à rattacher ensuite à
l'environnement.

### Bascule depuis un serveur

La bascule reprend les données du serveur : la base et les photos de profil. Les deux côtés tournent
sur la même version, qui doit gérer `AVATAR_DISK` (celle qui suit v1.7.0 ou une plus récente).

1. Configurer l'environnement (ci-dessus), puis déployer la version : les migrations créent un schéma
   vide.
2. Sur le serveur, passer le site en maintenance :

   ```sh
   runuser --user www-data -- php /var/www/portfolios/current/artisan down
   ```

3. Copier les données seules, sans la table des migrations ni les vues, que les migrations ont déjà
   créées :

   ```sh
   mysqldump --no-create-info --skip-triggers --complete-insert --single-transaction \
       --set-gtid-purged=OFF \
       --ignore-table=portfolios.migrations \
       --ignore-table=portfolios.account_reports \
       --ignore-table=portfolios.operation_reports \
       portfolios > /root/portfolios-data.sql
   ```

4. Activer l'accès public de la base dans Laravel Cloud, puis importer la copie avec les accès qu'il
   donne, et désactiver l'accès public :

   ```sh
   mysql --host=<hôte> --user=<utilisateur> --password --ssl-mode=REQUIRED <base> < /root/portfolios-data.sql
   ```

5. Copier les photos dans le bucket, avec une clef d'accès en lecture et écriture créée dans Laravel
   Cloud (section « Access keys » du bucket), par exemple avec [rclone](https://rclone.org) :

   ```sh
   RCLONE_CONFIG_R2_TYPE=s3 RCLONE_CONFIG_R2_PROVIDER=Cloudflare \
   RCLONE_CONFIG_R2_ACCESS_KEY_ID=<clef> RCLONE_CONFIG_R2_SECRET_ACCESS_KEY=<secret> \
   RCLONE_CONFIG_R2_ENDPOINT=<endpoint> \
       rclone copy /var/www/portfolios/shared/storage/app/private/avatars r2:<bucket>/avatars
   ```

   Supprimer ensuite cette clef.
6. Faire pointer le domaine vers Laravel Cloud, puis vérifier le site : connexion, photo de profil,
   envoi d'un mail (création d'un portfolio).
7. Arrêter le site du serveur, qui reste en maintenance, et son worker
   (`systemctl disable --now portfolios-queue`).

## Développement

### Installation

Le développement demande PHP 8.5 avec les extensions du déploiement, Composer, MySQL 8.0 ou plus
récent, et Node.js 22 au moins.

```sh
composer install
npm install
cp .env.model-dev .env
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
