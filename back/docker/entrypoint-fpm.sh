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
    su -s /bin/sh www-data -c "cd /var/www/html && php bin/console $*"
}

run_console doctrine:database:create --if-not-exists 2>/dev/null || true
run_console doctrine:migrations:migrate --no-interaction 2>/dev/null || true
run_console app:import-words 2>/dev/null || true

ensure_var_dirs

exec docker-php-entrypoint "$@"