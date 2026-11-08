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

## Déploiement

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

- PHP 8.5, en ligne de commande et en FPM, avec les extensions `intl`, `pdo_mysql` et `mbstring`
  (OPcache recommandé). Sur Debian ou Ubuntu, les paquets `php8.5-fpm`, `php8.5-cli`, `php8.5-intl`,
  `php8.5-mysql` et `php8.5-mbstring` viennent du dépôt d'Ondřej Surý.
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

2. Créer `/var/www/portfolios/deploy.sh` avec le contenu de la section « Script de déploiement »
   ci-dessous, puis le réserver à root :

   ```sh
   mkdir --parents /var/www/portfolios
   # … y créer deploy.sh, puis :
   chown root:root /var/www/portfolios /var/www/portfolios/deploy.sh
   chmod 755 /var/www/portfolios
   chmod 700 /var/www/portfolios/deploy.sh
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

1. Publier la release sur GitHub. La CI vérifie le code du tag, puis ajoute à la release l'archive et
   son empreinte (`portfolios-vX.Y.Z.tar.gz` et `portfolios-vX.Y.Z.tar.gz.sha256`).
2. Sur le serveur, une fois la CI terminée :

   ```sh
   /var/www/portfolios/deploy.sh vX.Y.Z
   ```

Avant les migrations, `deploy.sh` sauvegarde la base dans `backups/`
(`portfolios-<date>-<heure>-avant-vX.Y.Z.sql.gz`, dix gardées), par la commande
`php artisan db:dump` de l'application, qui passe par `mysqldump`. La copie est complète : tables et
données, vues, procédures et fonctions, déclencheurs, événements, jeu de caractères et collation de la
base. Une sauvegarde en échec ou incomplète arrête le déploiement.

Une sauvegarde supprime puis recrée la base : elle rend exactement l'état copié, sans les tables ou
objets créés depuis. Elle se restaure, site en maintenance, par un compte MySQL qui a tous les droits
sur la base (root, ou l'utilisateur `portfolios`) :

```sh
runuser --user www-data -- php /var/www/portfolios/current/artisan down
zcat /var/www/portfolios/backups/portfolios-…-avant-vX.Y.Z.sql.gz | mysql
runuser --user www-data -- php /var/www/portfolios/current/artisan up
```

Pour vérifier une sauvegarde sans toucher à la base en service, la restaurer sous un autre nom, ici
`portfolios_verification`. Le nom de la base figure dans la copie, sur ses lignes `DROP DATABASE`,
`CREATE DATABASE` et `USE` : `sed` le remplace sur ces seules lignes, dans une copie lisible par root
seul, à contrôler avant la restauration.

```sh
# Sous-shell : umask 077 (fichier lisible par root seul) ne vaut que pour cette commande.
(
    umask 077
    zcat /var/www/portfolios/backups/portfolios-…-avant-vX.Y.Z.sql.gz \
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
créé à la main.

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

À copier dans `/var/www/portfolios/deploy.sh`. Les variables du début s'adaptent au serveur : dépôt,
dossier de l'application, compte du serveur web, service PHP-FPM, nombre de versions gardées.

```bash
#!/usr/bin/env bash
#
# Déploie une version de Portfolios publiée sur GitHub.
#
# Usage, en root :
#     deploy.sh vX.Y.Z
#
# Étapes :
#     1. Téléchargement de l'archive de la release, construite par la CI, et contrôle de son
#        empreinte SHA-256.
#     2. Décompression dans releases/vX.Y.Z, reliée au .env et au storage/ partagés.
#     3. Mise en cache (configuration, routes, vues, icônes).
#     4. Sauvegarde de la base, compressée, dans backups/.
#     5. Migrations de la base.
#     6. Bascule du lien current vers la nouvelle version, d'un coup.
#     7. Rechargement de PHP-FPM et redémarrage du worker de file, dont le service systemd est créé
#        au premier déploiement.
#     8. Suppression des versions et des sauvegardes les plus anciennes.
#
# Retour arrière : une version encore présente sur le disque est réactivée sans téléchargement
# (étapes 3 à 8). Les migrations déjà passées restent en place : l'ancienne version tourne sur le
# schéma de la nouvelle. La sauvegarde de l'étape 4 permet de revenir aussi sur la base.
#
# Le script s'arrête à la première erreur. Tant que la bascule de l'étape 6 n'a pas eu lieu, la
# version en service reste celle d'avant.

# Mode strict de Bash. Par défaut, Bash affiche l'erreur d'une commande puis passe à la suivante :
# un déploiement continuerait sur une étape ratée.
#   -e           arrête le script dès qu'une commande échoue. Sans lui, des migrations en échec
#                n'empêcheraient pas la bascule vers la nouvelle version, et un « cd » raté ferait
#                tourner le nettoyage (rm -rf) dans un autre dossier.
#   -u           traite une variable non définie comme une erreur, au lieu d'une chaîne vide : une
#                faute de frappe dans un nom de variable ne peut pas changer la cible d'un rm -rf.
#   -o pipefail  fait échouer un tube (cmd1 | cmd2) dès que l'une de ses commandes échoue, et pas
#                seulement la dernière, pour que -e s'applique aussi aux tubes.
set -euo pipefail

# --- Paramètres, à adapter au serveur -----------------------------------------------------------------

# Dépôt GitHub dont les releases portent les archives.
REPOSITORY=sfaut/portfolios
# Dossier de l'application : il contient deploy.sh, current, releases/, shared/ et backups/.
APP_DIR=/var/www/portfolios
# Compte du serveur web (PHP-FPM, Nginx), propriétaire des fichiers que l'application écrit.
APP_USER=www-data
# Service systemd de PHP-FPM, rechargé après la bascule.
PHP_FPM_SERVICE=php8.5-fpm
# Service systemd du worker de file, créé par le script s'il n'existe pas.
QUEUE_SERVICE=portfolios-queue
# Nombre de versions gardées sur le disque, version active comprise, pour revenir en arrière.
KEEP_RELEASES=5
# Nombre de sauvegardes de la base gardées, une par déploiement.
KEEP_BACKUPS=10

# --- Contrôles ----------------------------------------------------------------------------------------

# Affiche un message d'erreur et arrête le script.
fail() {
    echo "deploy.sh : $*" >&2
    exit 1
}

# Affiche un avertissement, sans arrêter le script.
warn() {
    echo "deploy.sh : attention, $*" >&2
}

TAG=${1:-}
[[ $TAG =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail "version attendue au format vX.Y.Z (ex. deploy.sh v1.2.0)"
# root est nécessaire pour changer les propriétaires des fichiers et recharger PHP-FPM.
[[ $EUID -eq 0 ]] || fail "à lancer en root"
# Outil de sauvegarde de l'étape 4 (commande db:dump), fourni avec le client MySQL.
command -v mysqldump > /dev/null || fail "mysqldump introuvable (paquet du client MySQL)"

SHARED_DIR=$APP_DIR/shared
RELEASES_DIR=$APP_DIR/releases
RELEASE_DIR=$RELEASES_DIR/$TAG
BACKUPS_DIR=$APP_DIR/backups

# Premier lancement : sans configuration, le script crée shared/.env depuis le modèle de production
# de la version, y génère la clef de l'application, puis s'arrête pour qu'on renseigne le reste.
if [[ ! -f $SHARED_DIR/.env ]]; then
    mkdir --parents "$SHARED_DIR"
    curl --fail --location --silent --show-error --output "$SHARED_DIR/.env" \
        "https://raw.githubusercontent.com/$REPOSITORY/$TAG/.env.model-prod" \
        || { rm -f "$SHARED_DIR/.env"; fail ".env.model-prod introuvable dans la version $TAG"; }
    # Clef de chiffrement des cookies : 32 octets aléatoires, encodés en base64 comme le fait
    # « php artisan key:generate ».
    sed --in-place "s|^APP_KEY=\$|APP_KEY=base64:$(head --bytes=32 /dev/urandom | base64)|" "$SHARED_DIR/.env"
    # Écrit par root, lu par le serveur web, fermé aux autres comptes : il contient des mots de passe.
    chown "root:$APP_USER" "$SHARED_DIR/.env"
    chmod 640 "$SHARED_DIR/.env"
    echo "Premier lancement : $SHARED_DIR/.env créé, avec sa clef APP_KEY."
    echo "Y renseigner au moins APP_URL, DB_*, MAIL_* et APP_ADMIN_*, puis relancer : $0 $TAG"
    exit 1
fi

# Lance une commande Artisan de la version déployée sous le compte du serveur web : les fichiers
# qu'elle écrit (caches, journaux) lui appartiennent, et il pourra les réécrire ensuite.
artisan() {
    runuser --user "$APP_USER" -- php "$RELEASE_DIR/artisan" "$@"
}

# Dossier de travail, lisible par root seul (téléchargements), et dossier de
# décompression provisoire, caché (point initial) et renommé seulement une fois complet : une
# installation interrompue ne passe jamais pour une version prête. Les deux sont supprimés à la sortie
# du script, en succès comme en erreur.
work_dir=$(mktemp --directory)
partial_dir=$RELEASES_DIR/.$TAG.partial
trap 'rm -rf "$work_dir" "$partial_dir"' EXIT

# storage/ est partagé entre les versions : journaux, sessions et vues compilées survivent aux
# déploiements. Ses sous-dossiers, attendus par Laravel, sont créés au premier passage.
mkdir --parents "$SHARED_DIR"/storage/{app/private,app/public,framework/{cache/data,sessions,views},logs}
chown --recursive "$APP_USER:" "$SHARED_DIR/storage"
mkdir --parents "$RELEASES_DIR"
# Les sauvegardes contiennent toutes les données : root seul y accède.
mkdir --parents "$BACKUPS_DIR"
chmod 700 "$BACKUPS_DIR"

# --- 1 et 2. Téléchargement et installation de la version ---------------------------------------------

if [[ -d $RELEASE_DIR ]]; then
    # Version déjà installée (retour arrière, ou relance après une erreur aux étapes suivantes).
    echo "$TAG déjà présente : réactivation."
else
    archive=portfolios-$TAG.tar.gz
    url=https://github.com/$REPOSITORY/releases/download/$TAG

    # L'archive et son empreinte, ajoutées à la release par la CI. Le dépôt est public : aucun
    # identifiant n'est nécessaire.
    echo "Téléchargement de $archive…"
    for file in "$archive" "$archive.sha256"; do
        curl --fail --location --silent --show-error --output "$work_dir/$file" "$url/$file" \
            || fail "$file introuvable sur la release $TAG (CI terminée ?)"
    done
    # Une archive tronquée ou altérée est refusée avant toute décompression.
    (cd "$work_dir" && sha256sum --check --quiet "$archive.sha256") || fail "empreinte invalide pour $archive"

    rm -rf "$partial_dir"
    mkdir "$partial_dir"
    # --no-same-owner : les fichiers appartiennent à root, quel que soit leur propriétaire dans
    # l'archive.
    tar --extract --gzip --no-same-owner --file "$work_dir/$archive" --directory "$partial_dir"
    # La version lit la configuration et écrit dans le storage/ partagés.
    ln --symbolic "$SHARED_DIR/.env" "$partial_dir/.env"
    ln --symbolic "$SHARED_DIR/storage" "$partial_dir/storage"
    # Le code reste à root : le serveur web peut seulement écrire ses caches (bootstrap/cache) et
    # dans storage/.
    chown --recursive "$APP_USER:" "$partial_dir/bootstrap/cache"
    mv "$partial_dir" "$RELEASE_DIR"
fi

# --- 3. Mise en cache ---------------------------------------------------------------------------------

# Faite avant la sauvegarde : la configuration mise en cache, lue depuis le .env actuel, donne à
# l'étape 4 les accès à la base qu'utiliseront les migrations. Elle ne touche pas à la base.
echo "Mise en cache…"
artisan optimize

# --- 4. Sauvegarde de la base -------------------------------------------------------------------------

# La commande db:dump de l'application copie la base par mysqldump : copie complète (tables, données,
# vues, procédures, déclencheurs, événements) qui recrée la base à la restauration. Elle tourne sous le
# compte du serveur web, seul autorisé à lire le .env, et écrit la copie sur sa sortie, que root
# compresse ici dans backups/.
# La copie est écrite sous un nom provisoire, puis renommée une fois contrôlée : une sauvegarde
# interrompue ne passe jamais pour complète. umask 077 la rend lisible par root seul.
echo "Sauvegarde de la base…"
backup_file=$BACKUPS_DIR/portfolios-$(date +%Y%m%d-%H%M%S)-avant-$TAG.sql.gz
(
    umask 077
    artisan db:dump | gzip > "$backup_file.partial"
) || { rm -f "$backup_file.partial"; fail "sauvegarde de la base en échec : déploiement arrêté"; }
# Contrôle : fichier gzip intègre, et copie menée à son terme (mysqldump la termine par
# « -- Dump completed »).
if ! gzip --test "$backup_file.partial" \
    || ! zcat "$backup_file.partial" | tail --lines=1 | grep --quiet '^-- Dump completed'; then
    rm -f "$backup_file.partial"
    fail "sauvegarde de la base incomplète : déploiement arrêté"
fi
mv "$backup_file.partial" "$backup_file"
echo "Sauvegarde : $backup_file"

# --- 5. Migrations ------------------------------------------------------------------------------------

# Passées sur la nouvelle version avant la bascule : en cas d'échec, le site reste sur l'ancienne.
# Une migration doit donc rester compatible avec la version encore en service.
echo "Migrations…"
artisan migrate --force

# --- 6. Bascule ---------------------------------------------------------------------------------------

# Le nouveau lien est créé à côté, puis renommé sur current : un renommage est atomique, et aucune
# requête ne trouve current absent ou à moitié créé. La cible est relative (releases/vX.Y.Z), pour
# que le lien reste juste si APP_DIR est déplacé.
ln --symbolic --no-dereference --force "releases/$TAG" "$APP_DIR/current.new"
mv --no-target-directory "$APP_DIR/current.new" "$APP_DIR/current"
# La date du dossier sert au tri de l'étape 8 : la version active devient la plus récente.
touch "$RELEASE_DIR"

# --- 7. Rechargement des services ---------------------------------------------------------------------

# PHP-FPM garde en mémoire (OPcache, cache des chemins) des fichiers de l'ancienne version : le
# rechargement les oublie, sans couper les requêtes en cours.
systemctl reload "$PHP_FPM_SERVICE"
# Le worker de file termine sa tâche en cours puis s'arrête ; systemd le relance aussitôt dans
# current, donc sur la nouvelle version.
artisan queue:restart

# Le worker envoie les mails rangés dans la file (table jobs) : sans lui, ils restent en attente.
# Absent, son service est créé, démarré, et activé au démarrage du serveur ; systemd le relance s'il
# s'arrête. Présent, il est laissé tel quel (il a pu être adapté à la main) et seulement contrôlé.
if ! systemctl cat "$QUEUE_SERVICE" > /dev/null 2>&1; then
    echo "Création du service $QUEUE_SERVICE (worker de file)…"
    cat > "/etc/systemd/system/$QUEUE_SERVICE.service" <<EOF
[Unit]
Description=Portfolios - worker de file
After=network.target mysql.service

[Service]
User=$APP_USER
WorkingDirectory=$APP_DIR/current
ExecStart=$(command -v php) artisan queue:work --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
EOF
    systemctl daemon-reload
    systemctl enable --now "$QUEUE_SERVICE"
elif [[ $(systemctl show --property=WorkingDirectory --value "$QUEUE_SERVICE") != "$APP_DIR/current" ]]; then
    # Un service resté sur un autre dossier (ancienne installation…) ferait tourner un autre code.
    warn "le service $QUEUE_SERVICE ne tourne pas dans $APP_DIR/current (WorkingDirectory à corriger)"
elif ! systemctl is-active --quiet "$QUEUE_SERVICE"; then
    warn "le service $QUEUE_SERVICE est arrêté, les mails restent en attente (systemctl enable --now $QUEUE_SERVICE)"
fi

# --- 8. Nettoyage -------------------------------------------------------------------------------------

# Les versions sont triées de la plus récente à la plus ancienne (date du dossier) ; au-delà des
# KEEP_RELEASES premières, elles sont supprimées, sauf la version active.
cd "$RELEASES_DIR"
ls -1t | tail --lines=+$((KEEP_RELEASES + 1)) | while read -r old_release; do
    [[ $old_release == "$TAG" ]] || rm -rf "$old_release"
done
# De même pour les sauvegardes, au-delà des KEEP_BACKUPS plus récentes.
cd "$BACKUPS_DIR"
ls -1t -- *.sql.gz | tail --lines=+$((KEEP_BACKUPS + 1)) | while read -r old_backup; do
    rm -f -- "$old_backup"
done

echo "Portfolios $TAG déployée."
```

### Vérification du serveur

Le workflow GitHub Actions « Vérification du runner » (`.github/workflows/check-runner.yaml`), lancé à
la main, affiche depuis le runner auto-hébergé de production la version de PHP, les extensions
attendues et la place disque.

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
