FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-install pdo pdo_pgsql zip sockets

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN chmod +x build-app.sh

RUN composer install --no-interaction --optimize-autoloader --no-scripts

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

#RUN php artisan key:generate

RUN php artisan migrate --force
RUN php artisan db:seed --force
RUN php artisan optimize:clear
RUN php artisan config:cache

EXPOSE 8080
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
