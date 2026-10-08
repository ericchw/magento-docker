---
name: magento
description: Run common Magento Open Source maintenance and development workflows in this Docker Compose repository.
---

# Magento repository workflows

Use this skill for Magento work in this repository. Magento runs in the `php-fpm` Compose service and its source is in `src/`.

## Repository rules

- Run Magento CLI and Composer commands inside the `php-fpm` container as `www-data`: `docker compose exec -u www-data php-fpm ...`. The container's default exec user is `root`; avoid running commands that generate or modify Magento files as root.
- After commands that generate or deploy files, verify the affected paths are owned by `www-data` (or a group writable by PHP-FPM) and writable by the PHP-FPM user. Correct ownership narrowly on affected generated paths if needed; never use `777`.
- The stack is configured for developer mode; do not switch to production mode unless explicitly requested.
- Do not use `docker compose down -v`, remove volumes, reset the database, or overwrite Magento configuration unless the user explicitly requests a destructive reset.
- Do not run setup installation over an existing installation. The installer refuses to overwrite the configured app or a non-empty database.
- For database or configuration changes, state the affected service and expected impact before execution when the user has not explicitly requested that exact change.
- Prefer executing requested reversible maintenance actions directly. Do not run tests unless the user requests tests.
- Check `docker compose ps` and relevant logs when a command fails; report the command and concise error evidence.

## Action catalog

### `$magento flush-cache`

Flush Magento caches:

```bash
docker compose exec -u www-data php-fpm bin/magento cache:flush
```

Use `cache:clean` only when the requested scope is Magento cache types without flushing the backend storage.

### `$magento compile`

Compile dependency injection:

```bash
docker compose exec -u www-data php-fpm bin/magento setup:di:compile
```

This is appropriate after PHP class or DI configuration changes. In developer mode it is usually unnecessary for routine code edits; run it when requested or when a workflow requires generated DI code.

### `$magento deploy-static`

Force deploy English (US) static content, then flush cache:

```bash
docker compose exec -u www-data php-fpm bin/magento setup:static-content:deploy -f en_US
docker compose exec -u www-data php-fpm bin/magento cache:flush
```

Use the requested locale(s) when specified. Do not guess additional locales.

### `$magento compile-static`

Run the common combined deployment sequence:

```bash
docker compose exec -u www-data php-fpm bin/magento setup:di:compile
docker compose exec -u www-data php-fpm bin/magento setup:static-content:deploy -f en_US
docker compose exec -u www-data php-fpm bin/magento cache:flush
```

This can take time and regenerate files under `src/generated` and `src/pub/static`. Use it when explicitly requested or when the code change requires it; do not make it the default for every change.

### `$magento enable-admin-2fa`

Enable the Magento TwoFactorAuth module and flush cache:

```bash
docker compose exec -u www-data php-fpm bin/magento module:enable Magento_TwoFactorAuth
docker compose exec -u www-data php-fpm bin/magento setup:upgrade
docker compose exec -u www-data php-fpm bin/magento cache:flush
```

This changes module state and database schema. Admin users may need to configure an authenticator at their next login.

### `$magento disable-admin-2fa`

Disable Magento's admin TwoFactorAuth module and flush cache:

```bash
docker compose exec -u www-data php-fpm bin/magento module:disable Magento_TwoFactorAuth
docker compose exec -u www-data php-fpm bin/magento setup:upgrade
docker compose exec -u www-data php-fpm bin/magento cache:flush
```

Disabling 2FA reduces admin login protection. Clearly state this consequence when carrying out the action. Use the module command instead of editing `app/etc/config.php` by hand.

### `$magento status`

Inspect service and Magento status:

```bash
docker compose ps
docker compose exec -u www-data php-fpm bin/magento --version
docker compose exec -u www-data php-fpm bin/magento deploy:mode:show
```

### `$magento logs`

Show recent PHP service logs:

```bash
docker compose logs --tail=200 php-fpm
```

Add the affected service name when investigating another component.

### `$magento maintenance-on` and `$magento maintenance-off`

Toggle Magento maintenance mode:

```bash
docker compose exec -u www-data php-fpm bin/magento maintenance:enable
docker compose exec -u www-data php-fpm bin/magento maintenance:disable
```

Check status with `docker compose exec -u www-data php-fpm bin/magento maintenance:status`. Enabling maintenance mode affects storefront availability.

### `$magento index`

Inspect and reindex Magento indexes:

```bash
docker compose exec -u www-data php-fpm bin/magento indexer:status
docker compose exec -u www-data php-fpm bin/magento indexer:reindex
```

Use a specific indexer ID when only one index needs rebuilding.

### `$magento upgrade`

Run Magento database and module upgrade steps, then flush cache:

```bash
docker compose exec -u www-data php-fpm bin/magento setup:upgrade
docker compose exec -u www-data php-fpm bin/magento cache:flush
```

This can modify the database. Run it when requested or when a module change requires it.

## Other useful commands

Run the Magento CLI inside the container, for example:

```bash
docker compose exec -u www-data php-fpm bin/magento config:show
docker compose exec -u www-data php-fpm bin/magento cron:run
docker compose exec -u www-data php-fpm bin/magento indexer:status
docker compose exec -u www-data php-fpm composer install
```

For a manual fresh install, follow `README.md` and `./bin/install-magento`. Never infer that an existing database or project should be reset.
