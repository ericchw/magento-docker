#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${BACKUP_DIR:-$HOME/magento-docker-backup}"
COMPOSE_CONFIG="$(docker compose config --format json)"
COMPOSE_PROJECT="$(php -r '$c=json_decode(stream_get_contents(STDIN), true); echo $c["name"] ?? "";' <<<"$COMPOSE_CONFIG")"
APP_VOLUME="$(php -r '$c=json_decode(stream_get_contents(STDIN), true); echo $c["volumes"]["magento-app"]["name"] ?? (($c["name"] ?? "")."_magento-app");' <<<"$COMPOSE_CONFIG")"

usage() {
    echo "Usage: $0 <backup-directory-or-timestamp>" >&2
    echo "Example: $0 2026-10-05_14-30-00" >&2
}

if [[ $# -ne 1 ]]; then
    usage
    exit 2
fi

BACKUP="$1"
if [[ ! -d "$BACKUP" ]]; then
    BACKUP="$BACKUP_DIR/$BACKUP"
fi
if [[ ! -d "$BACKUP" ]]; then
    echo "Backup directory not found: $BACKUP" >&2
    exit 1
fi
BACKUP="$(cd -- "$BACKUP" && pwd)"

if [[ ! -f "$BACKUP/database.sql.gz" || ! -f "$BACKUP/magento-files.tar.gz" ]]; then
    echo "Backup must contain database.sql.gz and magento-files.tar.gz: $BACKUP" >&2
    exit 1
fi
gzip -t "$BACKUP/database.sql.gz"
tar -tzf "$BACKUP/magento-files.tar.gz" >/dev/null
if [[ -f "$BACKUP/compose-project" && "$(<"$BACKUP/compose-project")" != "$COMPOSE_PROJECT" ]]; then
    echo "Backup Compose project is '$(<"$BACKUP/compose-project")', current project is '$COMPOSE_PROJECT'." >&2
    echo "Use the matching Compose project name to avoid restoring into the wrong volumes." >&2
    exit 1
fi

cd "$PROJECT_DIR"
echo "This will replace the MariaDB databases and all files in volume $APP_VOLUME using: $BACKUP"
read -r -p 'Type RESTORE to continue: ' answer
if [[ "$answer" != RESTORE ]]; then
    echo "Restore cancelled."
    exit 1
fi

# Stop all writers and web workers before restoring a consistent database/files pair.
RESTART_SERVICES=0
restart_app_services() {
    if [[ "$RESTART_SERVICES" == 1 ]]; then
        docker compose up -d nginx php-fpm cron || true
    fi
}
trap restart_app_services EXIT
docker compose stop nginx php-fpm cron
RESTART_SERVICES=1
docker compose up -d mariadb
gzip -dc -- "$BACKUP/database.sql.gz" |
    docker compose exec -T mariadb sh -c \
        'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot'

# Mount the actual application volume by name and replace its contents from the archive.
docker run --rm -v "$APP_VOLUME:/var/www/html" -v "$BACKUP:/backup:ro" alpine:3.22 \
    sh -c 'find /var/www/html -mindepth 1 -maxdepth 1 -exec rm -rf -- {} + && tar -xzf /backup/magento-files.tar.gz -C /var/www/html'

# The archive omits transient runtime directories. Recreate Magento's writable
# paths and assign ownership using the PHP image identity; Alpine has another UID.
docker compose run --rm --no-deps --entrypoint sh php-fpm -c \
    'cd /var/www/html && mkdir -p var/cache var/page_cache var/view_preprocessed var/generation var/log var/report var/session var/tmp generated pub/static pub/media && chown -R www-data:www-data var generated pub/static pub/media'

docker compose up -d nginx php-fpm cron
RESTART_SERVICES=0
trap - EXIT

echo "Restore complete from: $BACKUP"
