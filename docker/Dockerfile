FROM dunglas/frankenphp:1.12.1-php8.5-alpine AS builder

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts && composer clear-cache

COPY . .

RUN apk add --no-cache nodejs npm
RUN npm install && npm run build



FROM dunglas/frankenphp:1.12.1-php8.5-alpine

WORKDIR /app

RUN apk add --no-cache zip libzip-dev \
    && docker-php-ext-install zip pcntl pdo pdo_mysql

COPY --from=builder /app /app

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 8000

# without octane
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]         
