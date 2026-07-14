#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
PORT="${PORT:-8888}"

cd "$ROOT"

if [[ ! -f vendor/autoload.php ]]; then
  composer install --no-interaction
fi

docker compose up -d

echo "Waiting for database..."
for _ in $(seq 1 30); do
  if docker compose ps --format json 2>/dev/null | grep -q healthy; then
    break
  fi
  sleep 2
done

echo "Starting ObiFunds at http://localhost:${PORT}"
php -S "localhost:${PORT}" -t .
