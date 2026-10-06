FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql zip gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Render injects $PORT at runtime; artisan serve must bind to it, not a
# fixed port. Migrations/seeders are idempotent (firstOrCreate throughout),
# so re-running them on every boot is safe and keeps a redeploy usable
# without a manual migration step.
#
# The queue worker (notifications, QUEUE_CONNECTION=database — see
# docs/HOW_IT_WORKS.md § Queue Workers) runs as a second process in this
# same container, backgrounded with `&`. Render's free tier has no
# separate worker-service type, so this is the only way to get queued
# jobs processed at all without a paid plan; it dies silently with the
# container rather than being supervised/restarted on its own, which is
# an accepted tradeoff for a free-tier deployment, not a real worker dyno.
CMD php artisan migrate --force \
    && php artisan db:seed --force \
    && php artisan storage:link || true \
    && (php artisan queue:work --tries=3 --sleep=3 &) \
    && php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
