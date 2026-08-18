FROM composer:2 AS test

WORKDIR /app

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock
COPY phpunit.xml /app/phpunit.xml
COPY tests /app/tests
COPY lib /app/lib
COPY schema.sql /app/schema.sql

RUN composer install --no-interaction --prefer-dist --no-progress --no-cache
RUN ./vendor/bin/phpunit

FROM php:8.2-cli-alpine

WORKDIR /app

COPY . /app

COPY --from=test /app/composer.lock /app/composer.lock

EXPOSE 8000

HEALTHCHECK --interval=60s --timeout=10s --start-period=15s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8000/login.php || exit 1

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]