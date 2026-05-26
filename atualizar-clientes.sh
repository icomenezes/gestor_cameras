#!/bin/bash
# atualizar-clientes.sh — Atualiza todos os clientes com a nova imagem
#
# Após rodar build-image.sh, este script reinicia cada container
# para pegar a nova imagem (zero-downtime por cliente sequencial).

set -euo pipefail

BASE_DIR="/opt/cameras"

if [ ! -d "$BASE_DIR" ]; then
    echo "Nenhum cliente encontrado em $BASE_DIR"
    exit 0
fi

for CLIENT_DIR in "$BASE_DIR"/*/; do
    SLUG=$(basename "$CLIENT_DIR")
    COMPOSE="$CLIENT_DIR/docker-compose.yml"

    if [ ! -f "$COMPOSE" ]; then
        continue
    fi

    echo "==> Atualizando cliente: $SLUG"
    cd "$CLIENT_DIR"
    docker compose pull app 2>/dev/null || true
    docker compose up -d --no-deps app
    echo "    OK: $SLUG"
done

echo ""
echo "Todos os clientes atualizados."
