#!/bin/sh
set -e

APP_DIR=/var/www/html

# Wait for MySQL — testa conectividade TCP via nc (disponível no busybox/Alpine)
echo "Aguardando MySQL em ${DB_HOST}:${DB_PORT:-3306}..."
until nc -z "${DB_HOST}" "${DB_PORT:-3306}" 2>/dev/null; do
    sleep 2
done
# Aguarda MySQL terminar de criar usuários após aceitar conexões TCP
sleep 5
echo "MySQL disponível."

cd "$APP_DIR"

# Gerar APP_KEY se não foi fornecida via env
if [ -z "$APP_KEY" ]; then
    export APP_KEY=$(php artisan key:generate --show --no-interaction)
    echo "APP_KEY gerada: ${APP_KEY:0:10}..."
fi

# Gravar .env para o Laravel
cat > "$APP_DIR/.env" << EOF
APP_NAME="${APP_NAME:-Sistema de Câmeras}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
APP_LOCALE=${APP_LOCALE:-pt_BR}
APP_TIMEZONE=${APP_TIMEZONE:-America/Sao_Paulo}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-cameras}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=${SESSION_DRIVER:-database}
CACHE_STORE=${CACHE_STORE:-database}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}

GO2RTC_URL=${GO2RTC_URL:-http://127.0.0.1:1984}
GO2RTC_PUBLIC_URL=${GO2RTC_PUBLIC_URL:-http://localhost/go2rtc}

FFMPEG_PATH=${FFMPEG_PATH:-ffmpeg}

ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.com}
ADMIN_PASSWORD=${ADMIN_PASSWORD:-Senha123}
ADMIN_NAME=${ADMIN_NAME:-Administrador}
EOF

php artisan cache:clear  --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache  --no-interaction
php artisan view:cache   --no-interaction
php artisan migrate --force --no-interaction
php artisan storage:link --no-interaction 2>/dev/null || true

# Criar admin inicial
php artisan db:seed --class=AdminSeeder --force --no-interaction

# Permissões
chown -R www-data:www-data storage bootstrap/cache

# Iniciar todos os serviços
exec /usr/bin/supervisord -c /etc/supervisord.conf
