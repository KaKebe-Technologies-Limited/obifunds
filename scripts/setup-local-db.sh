#!/usr/bin/env bash
# Run with: sudo ./scripts/setup-local-db.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$ROOT/.env"

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  echo "Run this script with sudo so MySQL root (auth_socket) can be used:"
  echo "  sudo ./scripts/setup-local-db.sh"
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing .env file at $ENV_FILE"
  exit 1
fi

DB_NAME="$(grep -E '^DB_NAME=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')"
DB_USER="$(grep -E '^DB_USER=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')"
DB_PASSWORD="$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | cut -d= -f2- | tr -d '"')"

DB_NAME="${DB_NAME:-obifunds}"
DB_USER="${DB_USER:-obifunds}"
DB_PASSWORD="${DB_PASSWORD:-obifunds}"

if [[ "$DB_USER" == "root" ]]; then
  echo "Switching DB_USER from root to obifunds (recommended on Ubuntu MySQL)."
  DB_USER="obifunds"
  if grep -q '^DB_USER=' "$ENV_FILE"; then
    sed -i 's/^DB_USER=.*/DB_USER=obifunds/' "$ENV_FILE"
  else
    echo "DB_USER=obifunds" >> "$ENV_FILE"
  fi
fi

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

mysql "${DB_NAME}" < "$ROOT/u850523537_ObiFunds.sql"

echo "Database ready:"
echo "  DB_NAME=${DB_NAME}"
echo "  DB_USER=${DB_USER}"
echo "Start the app with: ./start.sh"
