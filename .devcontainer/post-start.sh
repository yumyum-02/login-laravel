#!/bin/bash
# Codespace を開くたびに実行する。MySQL は compose が起動する。
set -euo pipefail

cd "$(dirname "$0")/.."

for _ in $(seq 1 30); do
  if php -r 'try { new PDO("mysql:host=mysql;port=3306;dbname=login_db_laravel", "laravel", "secret"); exit(0); } catch (Throwable $e) { exit(1); }'; then
    break
  fi
  sleep 2
done

if php -r 'exit(@fsockopen("127.0.0.1", 8000) ? 0 : 1);'; then
  exit 0
fi

nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel-serve.log 2>&1 &
