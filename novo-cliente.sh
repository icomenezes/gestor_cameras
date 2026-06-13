#!/bin/bash
# novo-cliente.sh — Provisiona um novo cliente isolado no servidor
#
# Uso:
#   ./novo-cliente.sh --slug empresa --email admin@empresa.com.br --password SenhaSegura123
#
# O slug vira subdomínio automático: empresa.camerasonline.net.br
# Para domínio personalizado, use --domain cameras.suaempresa.com.br
#
# O script:
#   1. Para e remove containers antigos do mesmo slug (se existirem)
#   2. Cria /opt/cameras/<slug>/ com docker-compose isolado
#   3. Sobe containers (app + mysql) na rede cameras_net
#   4. Registra slug→porta no mapa do nginx wildcard
#   5. Recarrega nginx

set -euo pipefail

###############################################################################
# Parâmetros
###############################################################################
SLUG=""
DOMAIN=""
ADMIN_EMAIL=""
ADMIN_PASSWORD=""

usage() {
    echo "Uso: $0 --slug <slug> --email <email> --password <senha> [--domain <dominio>]"
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --slug)     SLUG="$2";           shift 2 ;;
        --domain)   DOMAIN="$2";         shift 2 ;;
        --email)    ADMIN_EMAIL="$2";    shift 2 ;;
        --password) ADMIN_PASSWORD="$2"; shift 2 ;;
        *)          usage ;;
    esac
done

[[ -z "$SLUG" || -z "$ADMIN_EMAIL" || -z "$ADMIN_PASSWORD" ]] && usage

if ! [[ "$SLUG" =~ ^[a-z0-9-]+$ ]]; then
    echo "Erro: slug deve conter apenas letras minúsculas, números e hífen."
    exit 1
fi

# Domínio padrão = subdomínio wildcard
DOMAIN="${DOMAIN:-${SLUG}.camerasonline.net.br}"

###############################################################################
# Configurações
###############################################################################
BASE_DIR="/opt/cameras"
CLIENT_DIR="$BASE_DIR/$SLUG"
IMAGE_NAME="cameras-app:latest"
PORTS_MAP="/etc/nginx/cameras_slugs.map"
DOCKER_NETWORK="cameras_net"

###############################################################################
# Garantir que a rede Docker existe
###############################################################################
docker network inspect "$DOCKER_NETWORK" >/dev/null 2>&1 \
    || docker network create "$DOCKER_NETWORK"

###############################################################################
# Remove instalação anterior do mesmo slug (se existir)
###############################################################################
if [ -f "$CLIENT_DIR/docker-compose.yml" ]; then
    echo "==> Removendo instalação anterior de '$SLUG'..."
    cd "$CLIENT_DIR"
    docker compose down 2>/dev/null || true
    cd /
fi

# Remove entrada antiga do mapa nginx
if [ -f "$PORTS_MAP" ]; then
    sed -i "/^${DOMAIN}/d" "$PORTS_MAP"
fi

###############################################################################
# Auto-alocar porta livre (ainda exposta para debug/healthcheck local)
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
# Criar diretório e docker-compose do cliente
###############################################################################
echo "==> Criando $CLIENT_DIR"
mkdir -p "$CLIENT_DIR"

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

      GO2RTC_URL: "http://host.docker.internal:1984"
      GO2RTC_PUBLIC_URL: "https://${DOMAIN}/go2rtc"

      FFMPEG_PATH: /usr/bin/ffmpeg

      ADMIN_EMAIL: "${ADMIN_EMAIL}"
      ADMIN_PASSWORD: "${ADMIN_PASSWORD}"
      ADMIN_NAME: "Administrador"
    extra_hosts:
      - "host.docker.internal:host-gateway"
    networks:
      - cameras_net

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
    networks:
      - cameras_net

volumes:
  app_storage:
    name: cameras_${SLUG}_storage
  app_logs:
    name: cameras_${SLUG}_logs
  db_data:
    name: cameras_${SLUG}_db

networks:
  cameras_net:
    external: true
EOF

###############################################################################
# Subir containers
###############################################################################
echo "==> Subindo containers para $SLUG..."
cd "$CLIENT_DIR"
docker compose up -d

echo "==> Aguardando app ficar pronto (max 3 min)..."
for i in $(seq 1 60); do
    if curl -sf "http://127.0.0.1:${HTTP_PORT}/login" > /dev/null 2>&1; then
        echo "    App respondendo OK."
        break
    fi
    if [ $i -eq 60 ]; then
        echo "AVISO: App não respondeu em 3 min. Verifique: docker logs cameras_${SLUG}_app"
    fi
    sleep 3
done

###############################################################################
# Registrar no mapa nginx wildcard
###############################################################################
echo "==> Registrando $DOMAIN → porta $HTTP_PORT no nginx..."
echo "${DOMAIN}   ${HTTP_PORT};" >> "$PORTS_MAP"
nginx -t && systemctl reload nginx

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
App:  cameras_${SLUG}_app  (porta local: $HTTP_PORT)
DB:   cameras_${SLUG}_db
EOF
chmod 600 "$CREDS_FILE"

echo ""
echo "================================================================"
echo "  Cliente '$SLUG' provisionado com sucesso!"
echo "  URL:   https://$DOMAIN/login"
echo "  Email: $ADMIN_EMAIL"
echo "  Senha: $ADMIN_PASSWORD"
echo "  Credenciais: $CREDS_FILE"
echo "================================================================"
