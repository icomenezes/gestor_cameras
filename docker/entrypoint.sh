#!/bin/sh
set -e

APP_DIR=/var/www/html

# Wait for MySQL
echo "Aguardando MySQL em ${DB_HOST}:${DB_PORT:-3306}..."
until mysql -h"${DB_HOST}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -e "SELECT 1" > /dev/null 2>&1; do
    sleep 2
done
echo "MySQL disponível."

# Bootstrap Laravel
cd "$APP_DIR"

php artisan key:generate --no-interaction 2>/dev/null || true
php artisan config:cache --no-interaction
php artisan route:cache  --no-interaction
php artisan view:cache   --no-interaction
php artisan migrate --force --no-interaction
php artisan storage:link  --no-interaction 2>/dev/null || true

# Create initial admin if not exists
php artisan db:seed --class=AdminSeeder --no-interaction 2>/dev/null || true

# Fix permissions after potential volume mounts
chown -R www-data:www-data storage bootstrap/cache

# Start all services via supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf
