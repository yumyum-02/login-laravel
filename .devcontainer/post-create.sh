#!/bin/bash
# Codespace を codespaces ブランチで初めて作ったときだけ実行する。
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

if ! php -m | grep -qx bcmath; then
  sudo sh -c 'PHP_INI_DIR=/usr/local/etc/php docker-php-ext-install bcmath'
fi

if ! php -m | grep -qx pdo_mysql; then
  sudo sh -c 'PHP_INI_DIR=/usr/local/etc/php docker-php-ext-install pdo_mysql'
fi

composer install --no-interaction --prefer-dist
npm install --ignore-scripts
npm run build

ok=0
for _ in $(seq 1 40); do
  if php -r 'try { new PDO("mysql:host=mysql;port=3306;dbname=login_db_laravel", "laravel", "secret"); exit(0); } catch (Throwable $e) { exit(1); }'; then
    ok=1
    break
  fi
  sleep 3
done

if [ "$ok" -ne 1 ]; then
  echo "MySQL に接続できませんでした" >&2
  exit 1
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --ansi
fi

php artisan migrate --force --ansi
php artisan db:seed --force --ansi
php artisan storage:link --force --ansi || php artisan storage:link --ansi
