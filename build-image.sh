#!/bin/bash
# build-image.sh — Reconstrói a imagem cameras-app no servidor
#
# Rode após git pull para atualizar todos os clientes:
#   ./build-image.sh
#   ./atualizar-clientes.sh   (opcional: reinicia containers)

set -euo pipefail

# Garante permissões de execução nos scripts
chmod +x build-image.sh novo-cliente.sh atualizar-clientes.sh listar-clientes.sh remover-cliente.sh

echo "==> Construindo imagem cameras-app:latest..."
docker build -t cameras-app:latest .

echo "==> Imagem construída. Para atualizar os clientes:"
echo "    ./atualizar-clientes.sh"
