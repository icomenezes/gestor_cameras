#!/bin/bash
# novo-cliente.sh — Provisiona um novo cliente isolado no servidor
#
# Uso:
#   ./novo-cliente.sh --slug empresa --domain cameras.empresa.com.br \
#                     --email admin@empresa.com.br --password SenhaSegura123
#
# O script:
#   1. Para e remove containers/volumes antigos do mesmo slug (se existirem)
#   2. Cria /opt/cameras/<slug>/ com docker-compose isolado
#   3. Sobe container (app + mysql) em portas auto-alocadas
#   4. Gera config Nginx com SSL via Certbot
#   5. Imprime credenciais de acesso

set -euo pipefail
###############################################################################
# Parâmetros
###############################################################################
SLUG=""
DOMAIN=""
ADMIN_EMAIL=""
ADMIN_PASSWORD=""

usage() {
    echo "Uso: $0 --slug <slug> --domain <dominio> --email <email> --password <senha>"
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --slug)      SLUG="$2";           shift 2 ;;
        --domain)    DOMAIN="$2";         shift 2 ;;
        --email)     ADMIN_EMAIL="$2";    shift 2 ;;
        --password)  ADMIN_PASSWORD="$2"; shift 2 ;;
        *)           usage ;;
    esac
done

[[ -z "$SLUG" || -z "$DOMAIN" || -z "$ADMIN_EMAIL" || -z "$ADMIN_PASSWORD" ]] && usage

if ! [[ "$SLUG" =~ ^[a-z0-9-]+$ ]]; then
    echo "Erro: slug deve conter apenas letras minúsculas, números e hífen."
    exit 1
fi

###############################################################################
# Configurações globais
###############################################################################
BASE_DIR="/opt/cameras"
CLIENT_DIR="$BASE_DIR/$SLUG"
IMAGE_NAME="cameras-app:latest"

###############################################################################
# Remove instalação anterior do mesmo slug (se existir)
###############################################################################
if [ -f "$CLIENT_DIR/docker-compose.yml" ]; then
    echo "==> Removendo instalação anterior de '$SLUG'..."
    cd "$CLIENT_DIR"
    docker compose down -v 2>/dev/null || true
    cd /
fi

# Remove vhosts antigos deste slug
rm -f "/etc/nginx/sites-enabled/cameras_${SLUG}"
rm -f "/etc/nginx/sites-available/cameras_${SLUG}"

###############################################################################
# Auto-alocar portas livres
###############################################################################
find_free_port() {
    local port=$1
    while ss -tlnp | grep -q ":$port "; do
        ((port++))
    done
    echo $port
}

HTTP_PORT=$(find_free_port 8100)

DB_NAME="cameras_${SLUG//-/_}"
DB_USER="cam_${SLUG//-/_}"
DB_PASS=$(openssl rand -base64 16 | tr -d '/+=' | head -c 20)
DB_ROOT_PASS=$(openssl rand -base64 16 | tr -d '/+=' | head -c 20)

###############################################################################
# Criar diretório do cliente
###############################################################################
echo "==> Criando $CLIENT_DIR"
mkdir -p "$CLIENT_DIR"

###############################################################################
# Gerar docker-compose.yml do cliente
###############################################################################
cat > "$CLIENT_DIR/docker-compose.yml" <<EOF
# Cliente: $SLUG ($DOMAIN)
# Gerado em: $(date -u +"%Y-%m-%d %H:%M UTC")

services:
  app:
    image: $IMAGE_NAME
    container_name: cameras_${SLUG}_app
    restart: unless-stopped
    ports:
      - "127.0.0.1:${HTTP_PORT}:80"
    environment:
      APP_NAME: "Sistema de Câmeras"
      APP_ENV: production
      APP_KEY: ""
      APP_DEBUG: "false"
      APP_URL: "https://${DOMAIN}"
      APP_LOCALE: pt_BR
      APP_TIMEZONE: America/Sao_Paulo

      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: ${DB_NAME}
      DB_USERNAME: ${DB_USER}
      DB_PASSWORD: ${DB_PASS}

      SESSION_DRIVER: database
      CACHE_STORE: file
      QUEUE_CONNECTION: database

      GO2RTC_URL: "http://127.0.0.1:1984"
      GO2RTC_PUBLIC_URL: "https://${DOMAIN}/go2rtc"

      FFMPEG_PATH: /usr/bin/ffmpeg

      ADMIN_EMAIL: "${ADMIN_EMAIL}"
      ADMIN_PASSWORD: "${ADMIN_PASSWORD}"
      ADMIN_NAME: "Administrador"
    volumes:
      - app_storage:/var/www/html/storage/app
      - app_logs:/var/www/html/storage/logs
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.0
    container_name: cameras_${SLUG}_db
    restart: unless-stopped
    command: --default-authentication-plugin=mysql_native_password
    environment:
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASS}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-p${DB_ROOT_PASS}"]
      interval: 5s
      timeout: 5s
      retries: 15

volumes:
  app_storage:
    name: cameras_${SLUG}_storage
  app_logs:
    name: cameras_${SLUG}_logs
  db_data:
    name: cameras_${SLUG}_db
EOF

###############################################################################
# Subir containers
###############################################################################
echo "==> Subindo containers para $SLUG na porta $HTTP_PORT..."
cd "$CLIENT_DIR"
docker compose up -d

echo "==> Aguardando app ficar pronto (max 3 min)..."
for i in $(seq 1 60); do
    if curl -sf "http://127.0.0.1:${HTTP_PORT}/login" > /dev/null 2>&1; then
        echo "    App respondendo OK."
        break
    fi
    if [ $i -eq 60 ]; then
        echo "AVISO: App não respondeu em 3 minutos. Verifique: docker logs cameras_${SLUG}_app"
    fi
    sleep 3
done

###############################################################################
# Nginx vhost com HTTPS
###############################################################################
NGINX_CONF="/etc/nginx/sites-available/cameras_${SLUG}"

# Fase 1: HTTP temporário para Certbot validar
echo "==> Criando vhost Nginx temporário (HTTP)..."
cat > "$NGINX_CONF" <<EOF
server {
    listen 80;
    server_name ${DOMAIN};

    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        proxy_pass         http://127.0.0.1:${HTTP_PORT};
        proxy_http_version 1.1;
        proxy_set_header   Host \$host;
        proxy_set_header   X-Real-IP \$remote_addr;
        proxy_set_header   X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto http;
        proxy_read_timeout 300;
    }
}
EOF

ln -sf "$NGINX_CONF" "/etc/nginx/sites-enabled/cameras_${SLUG}"
nginx -t && systemctl reload nginx

# Fase 2: SSL com Certbot
echo "==> Obtendo/renovando certificado SSL para $DOMAIN..."
if certbot certonly --webroot -w /var/www/html \
    -d "$DOMAIN" --non-interactive --agree-tos \
    -m "ssl@trsystem.com.br" 2>/dev/null \
    || certbot certonly --nginx \
    -d "$DOMAIN" --non-interactive --agree-tos \
    -m "ssl@trsystem.com.br"; then

    # Fase 3: vhost HTTPS completo
    cat > "$NGINX_CONF" <<EOF2
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    server_name ${DOMAIN};

    ssl_certificate     /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    include             /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam         /etc/letsencrypt/ssl-dhparams.pem;

    client_max_body_size 500M;

    location /go2rtc/ {
        proxy_pass         http://127.0.0.1:${HTTP_PORT}/go2rtc/;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade \$http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host \$host;
        proxy_read_timeout 3600;
    }

    location / {
        proxy_pass         http://127.0.0.1:${HTTP_PORT};
        proxy_http_version 1.1;
        proxy_set_header   Host \$host;
        proxy_set_header   X-Real-IP \$remote_addr;
        proxy_set_header   X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto https;
        proxy_read_timeout 300;
    }
}
EOF2
    nginx -t && systemctl reload nginx
    echo "==> SSL configurado com sucesso."
else
    echo "AVISO: Certbot falhou. Verifique se DNS $DOMAIN aponta para este servidor."
    echo "       Rode depois: certbot certonly --nginx -d $DOMAIN"
fi

###############################################################################
# Salvar credenciais
###############################################################################
CREDS_FILE="$CLIENT_DIR/credenciais.txt"
cat > "$CREDS_FILE" <<EOF
Cliente: $SLUG
Domínio: $DOMAIN
Provisionado: $(date -u +"%Y-%m-%d %H:%M UTC")

=== Acesso Admin ===
URL:   https://$DOMAIN/login
Email: $ADMIN_EMAIL
Senha: $ADMIN_PASSWORD

=== Banco de Dados (interno ao container) ===
Host:  db (interno)
DB:    $DB_NAME
User:  $DB_USER
Pass:  $DB_PASS

=== Container ===
App:  cameras_${SLUG}_app  (porta: $HTTP_PORT)
DB:   cameras_${SLUG}_db
EOF
chmod 600 "$CREDS_FILE"

###############################################################################
# Resumo
###############################################################################
echo ""
echo "================================================================"
echo "  Cliente '$SLUG' provisionado com sucesso!"
echo "  URL:   https://$DOMAIN/login"
echo "  Email: $ADMIN_EMAIL"
echo "  Senha: $ADMIN_PASSWORD"
echo "  Credenciais salvas em: $CREDS_FILE"
echo "================================================================"
