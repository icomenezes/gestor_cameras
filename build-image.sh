#!/bin/bash
# build-image.sh — Reconstrói a imagem cameras-app no servidor
#
# Rode após git pull para atualizar todos os clientes:
#   ./build-image.sh
#   ./atualizar-clientes.sh   (opcional: reinicia containers)

set -euo pipefail

echo "==> Construindo imagem cameras-app:latest..."
docker build -t cameras-app:latest .

echo "==> Imagem construída. Para atualizar os clientes:"
echo "    ./atualizar-clientes.sh"
