#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/hat}"
COMPOSE_FILE="docker/docker-compose.prod.yml"

cd "$APP_DIR"

echo "==> Pull latest code"
git fetch origin main
git reset --hard origin/main

echo "==> Rebuild and restart containers"
docker compose -f "$COMPOSE_FILE" up --build -d --remove-orphans

echo "==> Wait for php + db"
for i in $(seq 1 60); do
  if docker exec hat_php php -v >/dev/null 2>&1 \
    && docker exec hat_mysql healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

echo "==> Fix var permissions"
docker exec -u root hat_php sh -c 'mkdir -p /var/www/html/var/cache /var/www/html/var/log && chown -R www-data:www-data /var/www/html/var && chmod -R ug+rwX /var/www/html/var'

echo "==> Ensure vendor (bind mount may hide image vendor)"
docker exec -u root hat_php sh -c 'cd /var/www/html && if [ ! -f vendor/autoload.php ]; then COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction; chown -R www-data:www-data vendor; fi'

echo "==> Reset database (drop all tables)"
docker exec -u www-data hat_php php bin/console doctrine:schema:drop --force --full-database --no-interaction

echo "==> Fresh migrations"
docker exec -u www-data hat_php php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Import dictionary"
docker exec -u www-data hat_php php bin/console app:import-words

echo "==> Clear prod cache"
docker exec -u www-data hat_php php bin/console cache:clear --env=prod --no-warmup
docker exec -u www-data hat_php php bin/console cache:warmup --env=prod
docker exec -u root hat_php sh -c 'chown -R www-data:www-data /var/www/html/var'

echo "==> Smoke: session/start"
SMOKE_PAYLOAD='{"teams":[{"name":"A","players":["a","b"]},{"name":"B","players":["c","d"]}],"total_words":40,"time_limit":60,"difficulties":[1,2,3],"categories":["clothes","animals","food"]}'
SMOKE_CODE="$(docker exec hat_web wget -qO- --server-response --post-data="$SMOKE_PAYLOAD" --header='Content-Type: application/json' http://127.0.0.1/api/session/start 2>&1 | awk '/^  HTTP\//{code=$2} END{print code+0}')"
echo "session/start HTTP: ${SMOKE_CODE}"
if [ "${SMOKE_CODE}" != "201" ]; then
  echo "==> Recent php logs"
  docker logs hat_php --tail 80 || true
  exit 1
fi

echo "==> Prune old images"
docker image prune -f

echo "==> Done"
