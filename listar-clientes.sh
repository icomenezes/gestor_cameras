#!/bin/bash
# listar-clientes.sh — Lista todos os clientes e status dos containers

BASE_DIR="/opt/cameras"

printf "%-20s %-40s %-10s\n" "SLUG" "DOMÍNIO" "STATUS"
printf "%-20s %-40s %-10s\n" "----" "-------" "------"

for CLIENT_DIR in "$BASE_DIR"/*/; do
    SLUG=$(basename "$CLIENT_DIR")
    COMPOSE="$CLIENT_DIR/docker-compose.yml"

    [ ! -f "$COMPOSE" ] && continue

    DOMAIN=$(grep "APP_URL" "$COMPOSE" | head -1 | sed 's/.*https:\/\///' | tr -d '"')
    STATUS=$(docker inspect --format='{{.State.Status}}' "cameras_${SLUG}_app" 2>/dev/null || echo "parado")

    printf "%-20s %-40s %-10s\n" "$SLUG" "$DOMAIN" "$STATUS"
done

