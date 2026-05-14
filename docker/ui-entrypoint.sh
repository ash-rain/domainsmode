#!/bin/sh
set -e

echo "[entrypoint] Waiting for MySQL to be ready..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'),getenv('DB_USERNAME'),getenv('DB_PASSWORD'));" > /dev/null 2>&1; do
  echo "[entrypoint] MySQL not ready yet, retrying in 2s..."
  sleep 2
done

echo "[entrypoint] Running UI migrations..."
php artisan migrate --force

echo "[entrypoint] Seeding default user..."
php artisan db:seed --force

echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm
