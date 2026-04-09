FROM dunglas/frankenphp:1.12.1

WORKDIR /app

RUN apt update && apt install zip libzip-dev -y && \
    docker-php-ext-install zip pcntl pdo pdo_mysql && \
    docker-php-ext-enable zip

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock /app/
COPY --chown=www-data:www-data . /app

RUN composer install

# lighter for prod
# RUN composer install --no-dev --optimize-autoloader


EXPOSE 8000

# without octane
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]         
