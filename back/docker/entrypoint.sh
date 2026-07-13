#!/bin/bash
set -e

cd /var/www/html

if [ ! -d "vendor" ] || [ -z "$(ls -A vendor 2>/dev/null)" ]; then
    composer install --prefer-dist --no-interaction
fi

php bin/console doctrine:database:create --if-not-exists 2>/dev/null || true
php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null || true
php bin/console app:import-words 2>/dev/null || true

exec apache2-foreground
