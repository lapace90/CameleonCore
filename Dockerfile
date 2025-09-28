# Utilise l'image officielle de PHP 8.4 avec FPM
FROM php:8.4-fpm-bookworm

# Installe les dépendances de système
RUN apt-get update && apt-get install -y \
    apt-transport-https \
    libpq-dev \
    openssl \
    unzip \
    zip \
    git \
    curl \
    libxml2-dev \
    libonig-dev && \
    rm -rf /var/lib/apt/lists/*

# Installe Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer --version

# Installe les extensions PHP
RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo_pgsql pgsql

# Active les extensions PHP
RUN docker-php-ext-enable pdo_pgsql

# ✅ Ajouter Xdebug ICI (après les extensions de base)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuration Xdebug pour les tests
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini


# Copie les fichiers du projet
WORKDIR /app
COPY . .

# Installe les dépendances de Composer
RUN composer install

# Installe API Platform
RUN composer require api-platform/laravel

# Expose le port 9000 pour PHP-FPM
EXPOSE 9000

# Démarre PHP-FPM
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
