#!/usr/bin/env sh
set -eu

# Compose supplies MySQL settings to the running application. Tests must never
# inherit them: PHPUnit runs against an in-memory SQLite database instead.
export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:
export DB_URL=
export CACHE_STORE=array
export SESSION_DRIVER=array
export SESSION_SECURE_COOKIE=false
export SECURITY_ENFORCE_CSRF=false
export LEGACY_PASSWORDS_ALLOWED=false

exec php artisan test "$@"
