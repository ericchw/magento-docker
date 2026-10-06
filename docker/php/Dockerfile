ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-bookworm

ARG COMPOSER_VERSION=2.10.0
ARG NEW_RELIC_AGENT_VERSION=12.10.0.39

ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    PATH=/var/www/html/bin:/var/www/html/vendor/bin:/root/.composer/vendor/bin:$PATH

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        default-mysql-client \
        git \
        gnupg \
        less \
        libbz2-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libldap2-dev \
        libmagickwand-dev \
        libonig-dev \
        libpng-dev \
        librabbitmq-dev \
        libsodium-dev \
        libssl-dev \
        libwebp-dev \
        libxml2-dev \
        libxslt1-dev \
        libzip-dev \
        unzip \
        vim-tiny \
        zip \
    && curl -fsSL https://deb.nodesource.com/setup_24.x -o /tmp/nodesource_setup.sh \
    && bash /tmp/nodesource_setup.sh \
    && apt-get install -y --no-install-recommends nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        bz2 \
        calendar \
        exif \
        ftp \
        gd \
        gettext \
        intl \
        ldap \
        mysqli \
        pcntl \
        pdo_mysql \
        soap \
        sockets \
        sodium \
        xsl \
        zip \
    && pecl install imagick redis amqp \
    && docker-php-ext-enable imagick redis amqp \
    && curl -fsSL "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar" -o /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && curl -fsSL https://github.com/aptible/supercronic/releases/download/v0.2.33/supercronic-linux-amd64 -o /usr/local/bin/supercronic \
    && chmod +x /usr/local/bin/supercronic \
    && curl -fsSL "https://download.newrelic.com/php_agent/archive/${NEW_RELIC_AGENT_VERSION}/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux.tar.gz" -o /tmp/newrelic.tar.gz \
    && mkdir -p /tmp/newrelic \
    && tar -xzf /tmp/newrelic.tar.gz -C /tmp/newrelic --strip-components=1 \
    && NR_INSTALL_PHPLIST=/usr/local/bin NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1 /tmp/newrelic/newrelic-install install \
    && rm -rf /var/lib/apt/lists/* /tmp/newrelic /tmp/newrelic.tar.gz /tmp/nodesource_setup.sh /tmp/pear \
    && mkdir -p /tmp/composer /tmp/composer-cache \
    && chown -R www-data:www-data /tmp/composer /tmp/composer-cache

COPY docker-entrypoint.sh /usr/local/bin/magento-docker-entrypoint
RUN chmod +x /usr/local/bin/magento-docker-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["magento-docker-entrypoint"]
CMD ["php-fpm"]
