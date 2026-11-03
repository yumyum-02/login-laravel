#!/bin/bash
# Codespace を初めて作ったときだけ実行する。
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  cp .env.example .env
fi

set_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

set_env DB_CONNECTION mysql
set_env DB_HOST mysql
set_env DB_PORT 3306
set_env DB_DATABASE login_db_laravel
set_env DB_USERNAME laravel
set_env DB_PASSWORD secret

composer install --no-interaction --prefer-dist

for _ in $(seq 1 30); do
  if php -r 'try { new PDO("mysql:host=mysql;port=3306;dbname=login_db_laravel", "laravel", "secret"); exit(0); } catch (Throwable $e) { exit(1); }'; then
    break
  fi
  sleep 2
done

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --ansi
fi

php artisan migrate --force --ansi
php artisan storage:link --force --ansi || php artisan storage:link --ansi
