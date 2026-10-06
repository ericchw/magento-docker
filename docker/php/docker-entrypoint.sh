#!/usr/bin/env bash
set -euo pipefail

# -----------------------------------------------------------------------------
# New Relic
# -----------------------------------------------------------------------------

if [ "${NEW_RELIC_ENABLED:-false}" = "true" ] && [ -n "${NEW_RELIC_LICENSE_KEY:-}" ]; then
  sed -i \
    -e "s/newrelic.license =.*/newrelic.license = \"${NEW_RELIC_LICENSE_KEY}\"/" \
    -e "s/newrelic.appname =.*/newrelic.appname = \"${NEW_RELIC_APP_NAME:-Magento 2.4.9 Local}\"/" \
    /usr/local/etc/php/conf.d/newrelic.ini || true
else
  if [ -f /usr/local/etc/php/conf.d/newrelic.ini ]; then
    sed -i \
      's/^extension = "newrelic.so"/;extension = "newrelic.so"/' \
      /usr/local/etc/php/conf.d/newrelic.ini || true
  fi
fi

# Recreate Composer's global Marketplace auth after container replacement. The
# final PHP-FPM startup step below transfers ownership to www-data and preserves
# Composer's private file modes for the FPM workers.
if [ -n "${MAGENTO_MARKETPLACE_PUBLIC_KEY:-}" ] \
  && [ -n "${MAGENTO_MARKETPLACE_PRIVATE_KEY:-}" ]; then
  mkdir -p "${COMPOSER_HOME:-/tmp/composer}"
  composer config --global http-basic.repo.magento.com \
    "$MAGENTO_MARKETPLACE_PUBLIC_KEY" \
    "$MAGENTO_MARKETPLACE_PRIVATE_KEY"
fi

# -----------------------------------------------------------------------------
# Magento project creation
# -----------------------------------------------------------------------------

if [ "${MAGENTO_AUTO_INSTALL:-false}" = "true" ] \
  && [ "$#" -gt 0 ] \
  && [ "$1" = "php-fpm" ] \
  && [ ! -f bin/magento ]; then

  mkdir -p /var/www/html

  if [ -n "${MARIADB_DATABASE:-}" ] && [ -n "${MARIADB_ROOT_PASSWORD:-}" ]; then
    existing_tables="$(MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -h mariadb -uroot "$MARIADB_DATABASE" -Nse 'SHOW TABLES')"
    if [ -n "$existing_tables" ]; then
      echo >&2
      echo "ERROR: Magento auto-install is enabled, but the configured database is not empty." >&2
      echo "Refusing to run setup:install over existing tables." >&2
      echo "Disable MAGENTO_AUTO_INSTALL or request an explicit database reset." >&2
      echo >&2
      exit 1
    fi
  else
    echo >&2
    echo "ERROR: MARIADB_DATABASE and MARIADB_ROOT_PASSWORD are required to check the auto-install database." >&2
    exit 1
  fi

  # Remove .gitkeep if it is the only file in the project directory.
  # Named volumes are initialized from the PHP image's initially empty workdir.
  # Refuse to write over unexpected content if a previous attempt was partial.
  if [ -n "$(find . -mindepth 1 -print -quit)" ]; then
    echo "ERROR: Magento volume is not empty, but bin/magento is missing." >&2
    echo "Inspect the volume or remove it explicitly before retrying." >&2
    exit 1
  fi

  # ---------------------------------------------------------------------------
  # Magento Marketplace authentication
  # ---------------------------------------------------------------------------

  if [ -n "${MAGENTO_MARKETPLACE_PUBLIC_KEY:-}" ] \
    && [ -n "${MAGENTO_MARKETPLACE_PRIVATE_KEY:-}" ]; then

    composer config --global http-basic.repo.magento.com \
      "${MAGENTO_MARKETPLACE_PUBLIC_KEY}" \
      "${MAGENTO_MARKETPLACE_PRIVATE_KEY}"

  elif [ ! -f auth.json ] \
    && [ ! -f "${COMPOSER_HOME:-/tmp/composer}/auth.json" ]; then

    echo >&2
    echo "ERROR: Magento Marketplace credentials are missing." >&2
    echo >&2
    echo "Set the following variables in env/composer.env:" >&2
    echo >&2
    echo "  MAGENTO_MARKETPLACE_PUBLIC_KEY=..." >&2
    echo "  MAGENTO_MARKETPLACE_PRIVATE_KEY=..." >&2
    echo >&2
    echo "Or provide a Composer auth.json file." >&2
    echo >&2
    exit 1
  fi

  # ---------------------------------------------------------------------------
  # Create Magento project
  # ---------------------------------------------------------------------------

  echo "Creating Magento ${MAGENTO_CREATE_PROJECT_VERSION:-2.4.9} project..."

  composer create-project \
    --repository-url=https://repo.magento.com/ \
    "magento/project-community-edition=${MAGENTO_CREATE_PROJECT_VERSION:-2.4.9}" \
    .

  echo "Magento project created successfully."
fi

# -----------------------------------------------------------------------------
# Magento installation
# -----------------------------------------------------------------------------

if [ "${MAGENTO_AUTO_INSTALL:-false}" = "true" ] \
  && [ "$#" -gt 0 ] \
  && [ "$1" = "php-fpm" ] \
  && [ -f bin/magento ] \
  && [ ! -f app/etc/env.php ]; then

  existing_tables="$(MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -h mariadb -uroot "$MARIADB_DATABASE" -Nse 'SHOW TABLES')"
  if [ -n "$existing_tables" ]; then
    echo >&2
    echo "ERROR: Magento auto-install is enabled, but the configured database is not empty." >&2
    echo "Refusing to run setup:install over existing tables." >&2
    exit 1
  fi

  # Do not use a weak fallback password.
  if [ -z "${MAGENTO_ADMIN_PASSWORD:-}" ]; then
    echo >&2
    echo "ERROR: MAGENTO_ADMIN_PASSWORD is required." >&2
    echo >&2
    echo "Set MAGENTO_ADMIN_PASSWORD in env/app.env." >&2
    echo >&2
    exit 1
  fi

  echo "Installing Magento..."

  bin/magento setup:install \
    --base-url="${MAGENTO_BASE_URL:-http://localhost/}" \
    --base-url-secure="${MAGENTO_BASE_URL_SECURE:-https://localhost/}" \
    --use-secure=1 \
    --use-secure-admin=1 \
    --db-host=mariadb \
    --db-name="${MARIADB_DATABASE:-magento}" \
    --db-user="${MARIADB_USER:-magento}" \
    --db-password="${MARIADB_PASSWORD:-magento}" \
    --backend-frontname="${MAGENTO_BACKEND_FRONTNAME:-admin}" \
    --admin-firstname="${MAGENTO_ADMIN_FIRSTNAME:-Admin}" \
    --admin-lastname="${MAGENTO_ADMIN_LASTNAME:-User}" \
    --admin-email="${MAGENTO_ADMIN_EMAIL:-admin@example.com}" \
    --admin-user="${MAGENTO_ADMIN_USER:-admin}" \
    --admin-password="${MAGENTO_ADMIN_PASSWORD}" \
    --language=en_US \
    --currency=CAD \
    --timezone=America/Vancouver \
    --use-rewrites=1 \
    --search-engine=opensearch \
    --opensearch-host=opensearch \
    --opensearch-port=9200 \
    --opensearch-index-prefix=magento2 \
    --opensearch-enable-auth=0 \
    --cache-backend=redis \
    --cache-backend-redis-server=valkey \
    --cache-backend-redis-db=0 \
    --page-cache=redis \
    --page-cache-redis-server=valkey \
    --page-cache-redis-db=1 \
    --session-save=redis \
    --session-save-redis-host=valkey \
    --session-save-redis-db=2 \
    --amqp-host=rabbitmq \
    --amqp-port=5672 \
    --amqp-user="${RABBITMQ_DEFAULT_USER:-magento}" \
    --amqp-password="${RABBITMQ_DEFAULT_PASS:-magento}" \
    --amqp-virtualhost=/

  bin/magento deploy:mode:set developer
  bin/magento cache:disable full_page block_html layout
  bin/magento cache:flush

  echo "Magento installation completed successfully."
fi

# -----------------------------------------------------------------------------
# Start the requested process
# -----------------------------------------------------------------------------

# Composer commands run by this entrypoint may run as root, while PHP-FPM
# workers run as www-data. Keep Composer's private auth.json owned by the worker
# so Magento can read it without making credentials world-readable.
if [ "${1:-}" = "php-fpm" ]; then
  composer_home="${COMPOSER_HOME:-/tmp/composer}"
  if [ -d "$composer_home" ]; then
    chown -R www-data:www-data "$composer_home"
  fi
fi

#exec docker-php-entrypoint "$@"

# -----------------------------------------------------------------------------
# Start the requested process
# -----------------------------------------------------------------------------

case "${1:-}" in
  supercronic)
    exec "$@"
    ;;
  php-fpm)
    exec docker-php-entrypoint "$@"
    ;;
  *)
    exec "$@"
    ;;
esac
