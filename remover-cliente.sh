#!/bin/bash
# remover-cliente.sh — Remove completamente um cliente
#
# Uso: ./remover-cliente.sh --slug empresa [--apagar-dados]
#
# Por padrão mantém os volumes (dados do cliente).
# Use --apagar-dados para remover também os volumes Docker.

set -euo pipefail

SLUG=""
APAGAR_DADOS=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --slug)        SLUG="$2"; shift 2 ;;
        --apagar-dados) APAGAR_DADOS=true; shift ;;
        *) echo "Uso: $0 --slug <slug> [--apagar-dados]"; exit 1 ;;
    esac
done

[ -z "$SLUG" ] && { echo "Uso: $0 --slug <slug>"; exit 1; }

CLIENT_DIR="/opt/cameras/$SLUG"

if [ ! -d "$CLIENT_DIR" ]; then
    echo "Cliente '$SLUG' não encontrado em $CLIENT_DIR"
    exit 1
fi

echo "ATENÇÃO: Esta ação irá remover o cliente '$SLUG'."
if [ "$APAGAR_DADOS" = true ]; then
    echo "         Os volumes (banco de dados e arquivos) também serão apagados!"
fi
read -p "Confirmar? (sim/não): " CONFIRM
[ "$CONFIRM" != "sim" ] && echo "Cancelado." && exit 0

# Parar e remover containers
cd "$CLIENT_DIR"
if [ "$APAGAR_DADOS" = true ]; then
    docker compose down -v
else
    docker compose down
fi

# Remover Nginx vhost
NGINX_CONF="/etc/nginx/sites-available/cameras_${SLUG}"
NGINX_LINK="/etc/nginx/sites-enabled/cameras_${SLUG}"
rm -f "$NGINX_LINK" "$NGINX_CONF"
nginx -t && systemctl reload nginx 2>/dev/null || true

# Remover diretório do cliente (mantém se não apagar dados)
if [ "$APAGAR_DADOS" = true ]; then
    rm -rf "$CLIENT_DIR"
    echo "Cliente '$SLUG' removido completamente."
else
    echo "Containers removidos. Diretório $CLIENT_DIR mantido com credenciais e compose."
fi
