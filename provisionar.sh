#!/bin/bash
# provisionar.sh — Wrapper chamado via sudo pelo signup.php
#
# Uso (interno, chamado pelo PHP):
#   sudo /var/www/cameras/provisionar.sh <slug>
#
# Lê email e senha de /tmp/signup_<slug>.json (criado pelo PHP),
# apaga o arquivo imediatamente, e executa novo-cliente.sh.
# O log de saída vai para /opt/cameras/logs/provision_<slug>.log

set -euo pipefail

SLUG="$1"
CONFIG_FILE="/tmp/signup_${SLUG}.json"
LOG_DIR="/opt/cameras/logs"
LOG_FILE="${LOG_DIR}/provision_${SLUG}.log"

if [ -z "$SLUG" ]; then
    echo "Uso: $0 <slug>" >&2
    exit 1
fi

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Arquivo de configuração não encontrado: $CONFIG_FILE" >&2
    exit 1
fi

mkdir -p "$LOG_DIR"

# Ler credenciais do JSON e apagar imediatamente
EMAIL=$(python3 -c "import json,sys; d=json.load(open('${CONFIG_FILE}')); print(d['email'])")
PASSWORD=$(python3 -c "import json,sys; d=json.load(open('${CONFIG_FILE}')); print(d['password'])")
rm -f "$CONFIG_FILE"

if [ -z "$EMAIL" ] || [ -z "$PASSWORD" ]; then
    echo "Credenciais inválidas no arquivo de configuração." >&2
    exit 1
fi

# Executar provisionamento com saída para log
exec /var/www/cameras/novo-cliente.sh \
    --slug "$SLUG" \
    --email "$EMAIL" \
    --password "$PASSWORD" \
    >> "$LOG_FILE" 2>&1
