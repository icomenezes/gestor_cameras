#!/bin/bash
# setup-servidor.sh — Configuração inicial do servidor para multi-tenant wildcard
#
# Execute UMA VEZ no servidor após o primeiro deploy:
#   bash setup-servidor.sh
#
# O que faz:
#   1. Cria a rede Docker compartilhada (cameras_net)
#   2. Cria o arquivo de mapa nginx (cameras_slugs.map)
#   3. Instala o vhost nginx wildcard para *.camerasonline.net.br
#   4. Instala o vhost nginx para o site principal camerasonline.net.br (com PHP)
#   5. Configura sudoers para www-data executar provisionar.sh
#   6. Cria diretório de logs de provisionamento
#   7. Recarrega nginx

set -euo pipefail

DOCKER_NETWORK="cameras_net"
PORTS_MAP="/etc/nginx/cameras_slugs.map"
NGINX_CONF="/etc/nginx/sites-available/cameras_wildcard"
NGINX_LINK="/etc/nginx/sites-enabled/cameras_wildcard"
DOMAIN_BASE="camerasonline.net.br"

echo "=== Setup do servidor cameras ==="

###############################################################################
# 1. Rede Docker compartilhada
###############################################################################
echo ""
echo "==> Verificando rede Docker '$DOCKER_NETWORK'..."
if docker network inspect "$DOCKER_NETWORK" >/dev/null 2>&1; then
    echo "    Rede já existe."
else
    docker network create "$DOCKER_NETWORK"
    echo "    Rede criada."
fi

###############################################################################
# 2. Arquivo de mapa nginx (slug → porta)
###############################################################################
echo ""
echo "==> Criando mapa nginx: $PORTS_MAP"
if [ ! -f "$PORTS_MAP" ]; then
    touch "$PORTS_MAP"
    echo "    Arquivo criado."
else
    echo "    Arquivo já existe (mantido)."
fi

###############################################################################
# 3. Vhost nginx wildcard
###############################################################################
echo ""
echo "==> Instalando vhost nginx wildcard para *.$DOMAIN_BASE..."

cat > "$NGINX_CONF" <<'NGINXEOF'
# Mapa: hostname completo → porta do container
map $host $backend_port {
    include /etc/nginx/cameras_slugs.map;
    default 0;
}

server {
    listen 80;
    server_name ~^[a-z0-9-]+\.camerasonline\.net\.br$;

    # Rejeita slugs não registrados
    if ($backend_port = "0") {
        return 404;
    }

    client_max_body_size 500M;

    # WebRTC/go2rtc com suporte a WebSocket
    location /go2rtc/ {
        proxy_pass         http://127.0.0.1:$backend_port/go2rtc/;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade    $http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_read_timeout 3600;
    }

    location / {
        proxy_pass         http://127.0.0.1:$backend_port;
        proxy_http_version 1.1;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_read_timeout 300;
    }
}
NGINXEOF

ln -sf "$NGINX_CONF" "$NGINX_LINK"

nginx -t && systemctl reload nginx
echo "    Nginx wildcard recarregado."

###############################################################################
# 4. Vhost nginx para o site principal (camerasonline.net.br)
###############################################################################
echo ""
echo "==> Instalando vhost nginx para o site principal $DOMAIN_BASE..."

MAIN_SITE_DIR="/var/www/camerasonline.net.br"
MAIN_CONF="/etc/nginx/sites-available/camerasonline_main"
MAIN_LINK="/etc/nginx/sites-enabled/camerasonline_main"

# Garante que o diretório do site existe
mkdir -p "$MAIN_SITE_DIR"

cat > "$MAIN_CONF" <<MAINEOF
server {
    listen 80;
    server_name camerasonline.net.br www.camerasonline.net.br;

    root $MAIN_SITE_DIR;
    index index.html index.php;

    # Arquivos estáticos
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # API PHP (signup, etc.)
    location /api/ {
        try_files \$uri \$uri/ =404;
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;
        fastcgi_index  index.php;
        include        fastcgi_params;
        fastcgi_param  SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param  SCRIPT_NAME     \$fastcgi_script_name;
        fastcgi_read_timeout 10;
    }

    # Logs
    access_log /var/log/nginx/camerasonline_main_access.log;
    error_log  /var/log/nginx/camerasonline_main_error.log;
}
MAINEOF

ln -sf "$MAIN_CONF" "$MAIN_LINK"
echo "    Vhost do site principal instalado."

###############################################################################
# 5. Detectar socket php-fpm correto e ajustar vhost
###############################################################################
echo ""
echo "==> Detectando PHP-FPM instalado..."

PHP_SOCK=""
for v in 8.3 8.2 8.1 8.0; do
    if [ -S "/run/php/php${v}-fpm.sock" ]; then
        PHP_SOCK="/run/php/php${v}-fpm.sock"
        echo "    Usando PHP ${v}: $PHP_SOCK"
        break
    fi
done

if [ -z "$PHP_SOCK" ]; then
    echo "    AVISO: Nenhum socket php-fpm encontrado. Instale php-fpm:"
    echo "    apt install php8.2-fpm && systemctl enable php8.2-fpm && systemctl start php8.2-fpm"
else
    # Substitui o socket no vhost
    sed -i "s|unix:/run/php/php8.2-fpm.sock|unix:${PHP_SOCK}|g" "$MAIN_CONF"
    echo "    Socket ajustado no vhost."
fi

###############################################################################
# 6. Sudoers — www-data pode executar provisionar.sh como root (sem senha)
###############################################################################
echo ""
echo "==> Configurando sudoers para provisionamento automático..."

SUDOERS_FILE="/etc/sudoers.d/cameras-provisioning"
cat > "$SUDOERS_FILE" <<'SUDOEOF'
# Permite que o PHP (www-data) chame o script de provisionamento de clientes
www-data ALL=(root) NOPASSWD: /var/www/cameras/provisionar.sh
SUDOEOF
chmod 0440 "$SUDOERS_FILE"

# Valida a sintaxe do sudoers
if visudo -c -f "$SUDOERS_FILE" >/dev/null 2>&1; then
    echo "    Sudoers configurado: $SUDOERS_FILE"
else
    echo "    ERRO: Sintaxe inválida em $SUDOERS_FILE — removendo para segurança."
    rm -f "$SUDOERS_FILE"
    exit 1
fi

###############################################################################
# 7. Diretório de logs de provisionamento + permissões do script
###############################################################################
echo ""
echo "==> Configurando permissões..."

mkdir -p /opt/cameras/logs
chmod 755 /opt/cameras/logs

# Garante que o script provisionar.sh é executável
chmod +x /var/www/cameras/provisionar.sh
chmod +x /var/www/cameras/novo-cliente.sh

echo "    Permissões configuradas."

###############################################################################
# 8. Reload final do nginx
###############################################################################
echo ""
nginx -t && systemctl reload nginx
echo "    Nginx recarregado."

###############################################################################
# 9. Resumo
###############################################################################
IP=$(curl -s ifconfig.me 2>/dev/null || echo 'IP_DO_SERVIDOR')
echo ""
echo "================================================================"
echo "  Servidor configurado com sucesso!"
echo ""
echo "  Próximos passos:"
echo ""
echo "  1. No Cloudflare, registros DNS:"
echo "     A  camerasonline.net.br          → $IP  (proxy ON)"
echo "     A  www.camerasonline.net.br      → $IP  (proxy ON)"
echo "     A  *.camerasonline.net.br        → $IP  (proxy ON)"
echo ""
echo "  2. SSL/TLS Cloudflare → modo: Full"
echo "     (Cloudflare entrega HTTPS; o servidor recebe HTTP)"
echo ""
echo "  3. Copie os arquivos do site para:"
echo "     $MAIN_SITE_DIR/"
echo "     cp -r /caminho/do/site/* $MAIN_SITE_DIR/"
echo ""
echo "  4. Copie a API de signup:"
echo "     cp /var/www/cameras/api/signup.php     $MAIN_SITE_DIR/api/"
echo "     cp /var/www/cameras/api/aguardando.html $MAIN_SITE_DIR/"
echo ""
echo "  5. Para provisionar um cliente manualmente:"
echo "     ./novo-cliente.sh --slug academia --email admin@academia.com --password Senha123"
echo "     Acesso: https://academia.$DOMAIN_BASE/login"
echo ""
echo "  6. Logs de provisionamento: /opt/cameras/logs/"
echo "================================================================"
