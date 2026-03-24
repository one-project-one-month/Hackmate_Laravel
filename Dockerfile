FROM composer:2.8 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM php:8.3-cli-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        icu-libs libzip libpng libjpeg-turbo freetype oniguruma postgresql-libs sqlite-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev postgresql-dev sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_sqlite mbstring intl zip bcmath gd \
    && apk del .build-deps \
    && addgroup -g 1000 app \
    && adduser -D -G app -u 1000 app

COPY --from=vendor /app /var/www/html

RUN mkdir -p storage bootstrap/cache \
    && chown -R app:app storage bootstrap/cache

USER app

EXPOSE 8000

CMD ["php","artisan","serve","--host=0.0.0.0","--port=8000"]
