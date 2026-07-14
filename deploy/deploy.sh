#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/hat}"
COMPOSE_FILE="docker/docker-compose.prod.yml"

cd "$APP_DIR"

echo "==> Pull latest code"
git fetch origin main
git reset --hard origin/main

echo "==> Rebuild and restart containers"
docker compose -f "$COMPOSE_FILE" up --build -d

echo "==> Prune old images"
docker image prune -f

echo "==> Done"
