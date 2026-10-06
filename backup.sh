#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${BACKUP_DIR:-$HOME/magento-docker-backup}"
DATE="$(date +"%Y-%m-%d_%H-%M-%S")"
DEST="$BACKUP_DIR/$DATE"
COMPOSE_CONFIG="$(docker compose config --format json)"
COMPOSE_PROJECT="$(php -r '$c=json_decode(stream_get_contents(STDIN), true); echo $c["name"] ?? "";' <<<"$COMPOSE_CONFIG")"

cd "$PROJECT_DIR"
mkdir -p "$BACKUP_DIR"
if [[ -e "$DEST" ]]; then
    echo "Backup destination already exists: $DEST" >&2
    exit 1
fi
mkdir -p "$DEST"

echo "Starting Magento backup: $DATE"

# Capture Magento databases and server objects. The dump is consistent for InnoDB tables.
docker compose exec -T mariadb sh -c \
    'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb-dump -uroot --all-databases --single-transaction --routines --triggers --events --hex-blob' \
    | gzip > "$DEST/database.sql.gz"

# Archive the actual Magento named volume, not a repository src/ directory.
docker run --rm --volumes-from "$(docker compose ps -q php-fpm)" \
    -v "$DEST:/backup" alpine:3.22 \
    sh -c 'cd /var/www/html && tar -czf /backup/magento-files.tar.gz --exclude=./var/cache --exclude=./var/page_cache --exclude=./var/view_preprocessed --exclude=./var/generation --exclude=./var/log --exclude=./var/report --exclude=./var/session --exclude=./var/tmp --exclude=./pub/static .'

gzip -t "$DEST/database.sql.gz"
tar -tzf "$DEST/magento-files.tar.gz" >/dev/null
printf '%s\n' "$COMPOSE_PROJECT" > "$DEST/compose-project"

echo "Backup complete: $DEST"
