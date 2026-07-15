#!/bin/bash
set -e

cd /var/www/html

ensure_var_dirs() {
    mkdir -p var/cache var/log
    chown -R www-data:www-data var
    chmod -R ug+rwX var
}

ensure_var_dirs

if [ ! -d "vendor" ] || [ -z "$(ls -A vendor 2>/dev/null)" ]; then
    composer install --prefer-dist --no-interaction
fi

run_console() {
    # Run as www-data so cache/log files stay writable by php-fpm
    su -s /bin/sh www-data -c "php bin/console $(printf '%q ' "$@")" || return $?
}

echo "==> doctrine:database:create"
run_console doctrine:database:create --if-not-exists || true

echo "==> doctrine:migrations:migrate"
run_console doctrine:migrations:migrate --no-interaction || true

WORD_COUNT="$(php bin/console dbal:run-sql "SELECT COUNT(*) AS c FROM word_pool" --quiet 2>/dev/null | tr -dc '0-9' || echo 0)"
if [ -z "$WORD_COUNT" ] || [ "$WORD_COUNT" -lt 1 ]; then
    echo "==> app:import-words (word_pool empty or missing)"
    run_console app:import-words || true
else
    echo "==> words already present ($WORD_COUNT), skip import"
fi

ensure_var_dirs

exec docker-php-entrypoint "$@"