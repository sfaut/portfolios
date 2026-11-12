#!/usr/bin/env bash
#
# Déploie une version de Portfolios publiée sur GitHub.
#
# Usage, en root :
#     deploy.sh vX.Y.Z
#
# Étapes :
#     1. Téléchargement de l'archive de la release, construite par la CI une fois ses tests passés,
#        et contrôle de son empreinte SHA-256.
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

    # L'archive et son empreinte, ajoutées à la release par la CI. Leur présence garantit que les
    # vérifications de la release (style, build, tests) sont terminées et réussies : la CI ne
    # construit l'archive qu'après elles (job package, « needs: [check] »), et l'ajoute à la release
    # en dernier. Le dépôt est public : aucun identifiant n'est nécessaire.
    echo "Téléchargement de $archive…"
    for file in "$archive" "$archive.sha256"; do
        curl --fail --location --silent --show-error --output "$work_dir/$file" "$url/$file" \
            || fail "$file introuvable sur la release $TAG : CI en cours ou en échec, ou release sans archive"
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
backup_file=$BACKUPS_DIR/portfolios.pre-$TAG.sql.gz
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
# Une sauvegarde déjà présente pour cette version (relance après une erreur, nouveau déploiement
# après un retour arrière) est gardée, renommée avec la date et l'heure de sa création : aucune
# sauvegarde n'est écrasée. Le nom simple désigne ainsi toujours la base d'avant le dernier
# déploiement de la version, la plus proche des données en service.
if [[ -f $backup_file ]]; then
    mv "$backup_file" "$BACKUPS_DIR/portfolios.pre-$TAG.$(date --reference="$backup_file" +%Y%m%d-%H%M%S).sql.gz"
fi
mv "$backup_file.partial" "$backup_file"
echo "Sauvegarde : $backup_file"

# --- 5. Migrations ------------------------------------------------------------------------------------

# Passées sur la nouvelle version avant la bascule : en cas d'échec, le site reste sur l'ancienne.
# Une migration doit donc rester compatible avec la version encore en service.
# MySQL n'annule pas un changement de schéma interrompu : une migration en échec peut laisser la base
# à moitié migrée. Il faut alors restaurer la sauvegarde de l'étape 4 avant de relancer le script,
# pour que la sauvegarde de la relance reparte de la base d'avant les migrations.
echo "Migrations…"
artisan migrate --force \
    || fail "migrations en échec, site toujours sur la version précédente." \
        "La base d'avant les migrations est dans $backup_file : la restaurer avant toute relance" \
        "(README, section « Mise à jour et retour arrière »)."

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
