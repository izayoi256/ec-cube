FROM php:7.1-apache

RUN set -x \
    && sed -i.bak -e "s%http://deb.debian.org/debian%http://ftp.riken.jp/pub/Linux/debian/debian%g" /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        sudo \
        git \
        libxml2-dev \
        unzip \
        wget \
        zlib1g-dev \
        iproute \
        libsqlite3-dev \
        libpq-dev \
        libicu-dev \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        pdo_pgsql \
        soap \
        xml \
        zip \
    && pecl install xdebug \
    && pecl install apcu \
    && docker-php-ext-enable \
        xdebug \
        apcu \
        opcache \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /tmp/*

RUN curl -sS https://getcomposer.org/installer \
    |  php -- \
        --filename=composer \
        --install-dir=/usr/local/bin \
    && COMPOSER_ALLOW_SUPERUSER=1 composer global require --optimize-autoloader "hirak/prestissimo" \
    && mkdir -p /var/www/.composer \
    && chown www-data /var/www/.composer \
    && chmod g+s /var/www/html

COPY ./my.ini /usr/local/etc/php/conf.d/my.ini
COPY ./my.conf /etc/apache2/conf-available/my.conf
COPY ./entrypoint.sh /entrypoint.sh

RUN a2enconf my

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
