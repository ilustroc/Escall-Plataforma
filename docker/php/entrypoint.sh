#!/usr/bin/env sh
set -eu

cd /var/www/html

# These dependencies live in a Docker volume, never in the Windows runtime.
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /var/www/.docker-runtime
chown -R www-data:www-data storage/framework bootstrap/cache /var/www/.docker-runtime
chmod -R ug+rwX storage bootstrap/cache /var/www/.docker-runtime

# The key is generated once in an isolated Docker volume. It never alters the
# repository's .env and is not committed to Git.
key_file=/var/www/.docker-runtime/app-key
if [ -f "$key_file" ]; then
    export APP_KEY="$(cat "$key_file")"
elif [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="$(php artisan key:generate --show)"
    (umask 077 && printf '%s' "$APP_KEY" > "$key_file")
fi

exec docker-php-entrypoint "$@"
