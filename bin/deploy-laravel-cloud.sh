#!/usr/bin/env bash
#
# Déploie une version de Portfolios sur Laravel Cloud.
#
# Usage, depuis un clone du dépôt :
#     bin/deploy-laravel-cloud.sh vX.Y.Z
#
# Étapes :
#     1. Lecture du deploy hook de l'environnement Laravel Cloud dans le .env du clone
#        (LARAVEL_CLOUD_DEPLOY_HOOK).
#     2. Récupération des tags, puis contrôle du commit du tag : Laravel Cloud ne déploie que les
#        commits de la branche de l'environnement.
#     3. Contrôle de la release GitHub : son archive n'y est ajoutée qu'une fois les vérifications de
#        la CI passées.
#     4. Confirmation, puis appel du deploy hook avec le commit du tag.
#
# Laravel Cloud construit ensuite l'application (build commands), lance les deploy commands
# (migrations) et bascule vers la nouvelle version. Le déploiement se suit dans Laravel Cloud.
#
# Retour arrière : relancer le script avec une version précédente. Les migrations déjà passées
# restent en place, comme avec deploy.sh.

# Mode strict de Bash (détails dans deploy.sh) : arrêt à la première erreur, variable non définie
# traitée comme une erreur, tube en échec dès que l'une de ses commandes échoue.
set -euo pipefail

# --- Paramètres -------------------------------------------------------------------------------------

# Dépôt GitHub dont les releases portent les archives.
REPOSITORY=sfaut/portfolios
# Branche de l'environnement Laravel Cloud.
BRANCH=master

# --- Contrôles ----------------------------------------------------------------------------------------

# Affiche un message d'erreur et arrête le script.
fail() {
    echo "deploy-laravel-cloud.sh : $*" >&2
    exit 1
}

TAG=${1:-}
[[ $TAG =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail "version attendue au format vX.Y.Z (ex. bin/deploy-laravel-cloud.sh v1.2.0)"

cd "$(dirname "$0")/.."

# 1. Deploy hook, lu sans charger le .env dans le shell : sa syntaxe n'est pas celle de Bash, et
#    « source » l'exécuterait. L'URL n'est jamais affichée : elle suffit à lancer un déploiement.
[[ -f .env ]] || fail "fichier .env introuvable à la racine du dépôt"
hook=$(sed --quiet 's/^LARAVEL_CLOUD_DEPLOY_HOOK=//p' .env | tail --lines=1)
hook=${hook%$'\r'}
hook=${hook#\"}
hook=${hook%\"}
[[ -n $hook ]] || fail "LARAVEL_CLOUD_DEPLOY_HOOK est vide dans .env : y copier l'URL du deploy hook de l'environnement"
[[ $hook == https://* ]] || fail "LARAVEL_CLOUD_DEPLOY_HOOK doit être une adresse https://"

for command in git gh curl; do
    command -v "$command" > /dev/null || fail "commande $command introuvable"
done

# 2. Commit du tag, présent sur la branche de l'environnement.
git fetch --quiet --tags origin "$BRANCH"
commit=$(git rev-parse --verify --quiet "refs/tags/$TAG^{commit}") || fail "tag $TAG introuvable"
git merge-base --is-ancestor "$commit" "origin/$BRANCH" \
    || fail "le commit de $TAG n'est pas sur origin/$BRANCH, la branche de l'environnement"

# 3. Release vérifiée par la CI : l'archive n'y est ajoutée qu'une fois les vérifications passées.
assets=$(gh release view "$TAG" --repo "$REPOSITORY" --json assets --jq '.assets[].name') \
    || fail "release $TAG introuvable sur GitHub"
grep --quiet --line-regexp --fixed-strings "portfolios-$TAG.tar.gz" <<< "$assets" \
    || fail "la release $TAG n'a pas encore son archive : CI en cours ou en échec"

# 4. Confirmation, puis déploiement.
read -r -p "Déployer Portfolios $TAG (${commit:0:7}) sur Laravel Cloud ? [o/N] " answer
[[ $answer == [oO] ]] || fail "déploiement annulé"

separator='?'
[[ $hook == *\?* ]] && separator='&'

response=$(curl --fail-with-body --silent --show-error --request POST "$hook${separator}commit_hash=$commit") \
    || fail "appel du deploy hook en échec${response:+ : $response}"

echo "Déploiement de Portfolios $TAG demandé à Laravel Cloud, à suivre dans ses déploiements."
