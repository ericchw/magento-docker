<p align="center">Magento Open Source 2.4.9 Docker Development Environment</p>

<p align="center">
  <a href="README.md">English</a> |
  <a href="README.zh-TW.md">繁體中文</a>
</p>


Compose is configured for Magento Open Source 2.4.9. PHP uses the official PHP 8.5 FPM image as the base for a custom image; the other active services use pinned image tags. The database, search index, queue, Valkey, Mailpit, Composer cache, and Magento project code use named volumes, so `docker compose down` does not remove data. Magento source code is stored in the `magento-app` named volume mounted at `/var/www/html` in the PHP, cron, and Nginx containers.

| Component | Version |
| --- | --- |
| PHP | 8.5 FPM (official `php` image base) |
| Composer | 2.10.0 |
| OpenSearch | 3.3.0 |
| MariaDB | 12.3.3 |
| RabbitMQ | 4.3.6 |
| Valkey | 9.0.2 |
| nginx | 1.30.5-alpine3.24 |
| Mailpit | 1.27.8 |
| Adminer | 5.4.1 |

## Services

- `nginx`: web entrypoint on `http://{{domain_name}}` (port 80) and `https://{{domain_name}}` (port 443)
- `php-fpm`: Magento PHP runtime with Composer, AMQP, Redis/Valkey, Imagick, and New Relic
- `cron`: Magento cron runner
- `mariadb`: database
- `opensearch`: catalog search
- `rabbitmq`: default Magento message queue
- ActiveMQ Artemis is currently commented out in `compose.yaml` and is not started.
- `valkey`: cache, page cache, sessions, and locking
- `mailpit`: local email UI at `http://localhost:8025`
- `adminer`: database UI at `http://localhost:8081`

## Quick Start and Installation

```bash
for name in app artemis composer cron db newrelic opensearch rabbitmq valkey; do
  cp "env/${name}.env.example" "env/${name}.env"
done
```

The Magento project is created in `src/` by the first automatic installation. This flow is intended for a fresh project directory and database; do not use it for an existing Magento installation.

In `env/app.env`, `env/db.env`, `env/rabbitmq.env`, `env/valkey.env`, and `env/opensearch.env`, replace every `ChangeThis-...` value with a strong password. `env/artemis.env.example` is provided for future Artemis use, but Artemis is disabled in Compose. Add your Magento Marketplace public and private access keys to `env/composer.env`. Actual `env/*.env` files are ignored by Git; the example files are tracked.

Ensure `env/composer.env` contains `MAGENTO_CREATE_PROJECT_VERSION=2.4.9` and `MAGENTO_AUTO_INSTALL=true`, then run:

```bash
docker compose build
docker compose up -d
docker compose logs -f php-fpm
```

### Local HTTPS Certificate

Nginx always reads the generic filenames `docker/nginx/certs/localhost.crt` and `docker/nginx/certs/localhost.key`; there is no need to rename them for a different domain. The certificate and private key are ignored by Git. If they do not exist yet, use OpenSSL to create a local self-signed certificate:

```bash
mkdir -p docker/nginx/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/certs/localhost.key \
  -out docker/nginx/certs/localhost.crt \
  -subj "/CN={{domain_name}}" \
  -addext "subjectAltName=DNS:{{domain_name}},DNS:localhost,IP:127.0.0.1"
```

If you change the domain, issue a new certificate and add the new domain to its Subject Alternative Name (SAN). Keep the filenames unchanged.

Once the services are healthy, the PHP container downloads Magento 2.4.9 with Composer and runs `setup:install`. After installation completes, press `Ctrl-C` to leave the logs; the containers will keep running. Open `http://{{domain_name}}` or `https://{{domain_name}}`; the admin path is `/admin`. Check service status with `docker compose ps`.

The first installation requires network access and valid Marketplace keys. If it fails, check `docker compose logs php-fpm` first. Automatic installation will not overwrite an existing database or a non-empty project volume. After fixing the cause, clear the data only if you intend to reinstall from scratch:

```bash
docker compose down -v
docker compose up -d
```

`down -v` deletes the Magento project volume, database, search index, queue, Composer cache, Valkey data, and Mailpit data.

## Data Storage and Backups

The running Magento project files live in the `magento-app` Docker named volume at `/var/www/html`. MariaDB, OpenSearch, RabbitMQ, Valkey, Mailpit, and Composer cache data are stored in their corresponding named volumes. `docker compose down` preserves these volumes; `docker compose down -v` removes them.

`./backup.sh` writes a timestamped backup directory under `$HOME/magento-docker-backup` by default. Set `BACKUP_DIR` to choose another destination. It creates `database.sql.gz` (MariaDB databases and server objects) and `magento-files.tar.gz` (the actual `magento-app` volume, including media and `app/etc` configuration). It validates both archives and records the Compose project name.

`./restore.sh <backup-directory-or-timestamp>` validates both archives and requires typing `RESTORE`. It stops nginx, php-fpm, and cron, replaces the MariaDB contents and the application volume contents, then starts those services again. This is destructive: take and verify a fresh backup before restoring, and ensure the archive belongs to this Compose project. The restore script leaves other service volumes (OpenSearch, RabbitMQ, Valkey, Mailpit, and Composer cache) untouched.

Backups contain `app/etc/env.php`, including the Magento encryption key, and may contain customer/order data. Protect the backup directory and copy backups off the Docker host. Keep Docker configuration, real `env/*.env` secrets, and TLS certificates separately; the scripts do not back them up. Test recovery in a separate environment before relying on a backup for disaster recovery.

## Install Magento Open Source 2.4.9

Adobe Marketplace credentials are required to download Magento packages. Put `MAGENTO_MARKETPLACE_PUBLIC_KEY` and `MAGENTO_MARKETPLACE_PRIVATE_KEY` in `env/composer.env`; keep real credentials private and out of version control. The actual `env/*.env` files are ignored by Git; `env/*.env.example` files contain placeholders.

```dotenv
MAGENTO_MARKETPLACE_PUBLIC_KEY=your-public-key
MAGENTO_MARKETPLACE_PRIVATE_KEY=your-private-key
```

For Hyvä, put the Packagist key and project name from Hyvä Portal → Licenses → Composer credentials in `env/hyva.env` as `HYVA_PACKAGIST_KEY` and `HYVA_PACKAGIST_PROJECT`. Compose passes these values to `php-fpm`, and the PHP entrypoint configures Composer's private Hyvä repository when both values are set. The actual `env/hyva.env` file is ignored by Git; use `env/hyva.env.example` as a template. After adding credentials, recreate `php-fpm` so Composer can load the credentials, then follow Hyvä's theme installation and Tailwind build instructions.

To install manually (if automatic installation was disabled):

```bash
./bin/install-magento
```

`./bin/install-magento` reads its configuration from the env files and refuses to overwrite an existing installation or a non-empty database.

Use the Codex CLI repo-local `$magento ACTION` skill for common Magento workflows. Its action catalog is in `.codex/skills/magento/SKILL.md`; actions include `$magento flush-cache`, `$magento compile-static`, `$magento enable-admin-2fa`, `$magento disable-admin-2fa`, `$magento maintenance-on`, and `$magento index`. The PHP entrypoint repairs Composer home ownership whenever PHP-FPM starts.

Automatic installation is enabled by default. To switch to manual installation, set `MAGENTO_AUTO_INSTALL=false` in `env/composer.env`, run `docker compose up -d`, then run `./bin/install-magento`.

The installation script connects to MariaDB, OpenSearch, Valkey, and RabbitMQ using their Compose service names. It uses the `America/Vancouver` timezone and `CAD` currency.

## Nginx Configuration

The active configuration is in `docker/nginx/conf.d/default.conf`. It listens on ports 80 and 443, uses `/var/www/html/pub` as the document root, routes Magento requests through `index.php`, and applies Magento's static and media fallbacks. It denies access to cron, customer/download/import media, and other PHP files. Nginx expects `docker/nginx/certs/localhost.crt` and `localhost.key`; create these before starting the stack. They are local self-signed certificates, so browsers will show a trust warning. Use valid certificates outside local development.

## New Relic

Set these values in `env/newrelic.env` before building or restarting PHP services:

```dotenv
NEW_RELIC_LICENSE_KEY=your-license-key
NEW_RELIC_APP_NAME=Magento 2.4.9 Local
```

Then restart:

```bash
docker compose up -d --build php-fpm cron
```

## RabbitMQ and ActiveMQ Artemis

RabbitMQ is wired into the Magento install command as the message queue backend. Artemis is currently disabled in `compose.yaml`; the following endpoints are available only if you enable and configure its Compose service:

- RabbitMQ AMQP: `rabbitmq:5672`
- RabbitMQ management UI: `http://localhost:15672`
- Artemis broker: `artemis:61616`
- Artemis console: `http://localhost:8161`

Magento Open Source does not automatically configure both brokers for the same queue topology during `setup:install`. The current installation uses RabbitMQ.

## Useful Commands

```bash
docker compose ps
docker compose logs -f php-fpm nginx
docker compose exec php-fpm bash
docker compose exec php-fpm bin/magento cache:flush
docker compose down
docker compose down -v
```

`docker compose down -v` deletes database, search, queue, and cache volumes.

## Aeromalls user guides

Detailed guides are kept in `docs/` (Traditional Chinese / Hong Kong):

- [Customer order cancellation](docs/customer-order-cancellation.zh-HK.md)
- [Order numbering and the one-time migration](docs/order-numbering.zh-HK.md)
- [Stripe payment retries and webhook troubleshooting](docs/stripe-payment-retry.zh-HK.md)
- [Page Builder brand marquee and slider](docs/pagebuilder-marquee.zh-HK.md)
- [OpenSearch recovery](docs/opensearch-troubleshooting.zh-HK.md)

The payment features require the separately installed Stripe Payments extension
(the implementation was developed against version 4.7.0). It is not included in
the repository's Composer lockfile. Page Builder and the licensed Hyvä theme
are also required.

Database changes described in the guides are not applied by a Git checkout.
The order-number migration is a manual, one-time script targeting order entity
ID 1; review its target and take a backup before using it.
