# syntax=docker/dockerfile:1
# =====================================================================
# VoluntaRed - imagen de la aplicación (PHP 8.2 + Apache)
# Se usa junto con docker-compose.yml:  docker compose up -d --build
# =====================================================================

# ---------------------------------------------------------------- dependencias de Composer
FROM composer:2 AS dependencias
WORKDIR /app
COPY composer.json composer.lock ./
# Incluye las dependencias de desarrollo (PHPUnit) para poder ejecutar las pruebas en el contenedor.
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader --ignore-platform-reqs

# ---------------------------------------------------------------- aplicación
FROM php:8.2-apache

ENV TZ=America/Bogota \
    COMPOSER_ALLOW_SUPERUSER=1

# Extensiones de PHP (pdo_mysql, zip), cliente de MariaDB para los respaldos y módulos de Apache.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev mariadb-client tzdata \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini "$PHP_INI_DIR/conf.d/voluntared.ini"
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/cliente-mariadb.cnf /etc/mysql/conf.d/voluntared.cnf

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=dependencias /app/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize --no-interaction \
    && mkdir -p storage/documentos storage/correos storage/logs storage/respaldos public/uploads/fotos \
    && chown -R www-data:www-data storage public/uploads \
    && sed -i 's/\r$//' docker/entrypoint.sh \
    && install -m 0755 docker/entrypoint.sh /usr/local/bin/voluntared-entrypoint

EXPOSE 80
ENTRYPOINT ["voluntared-entrypoint"]
CMD ["apache2-foreground"]
