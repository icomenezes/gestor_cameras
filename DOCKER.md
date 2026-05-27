# Infraestrutura Docker — Sistema de Câmeras SaaS

Cada cliente roda em um container isolado com banco de dados próprio, go2rtc próprio e domínio próprio.

---

## Estrutura no servidor

```
/opt/cameras/
├── trsystem/
│   ├── docker-compose.yml
│   └── credenciais.txt
├── cliente-abc/
│   ├── docker-compose.yml
│   └── credenciais.txt
└── ...

/var/www/cameras/        ← código fonte + scripts
/etc/nginx/sites-available/cameras_<slug>  ← vhost de cada cliente
```

---

## Pré-requisitos (já configurado no servidor)

- Docker + Docker Compose instalados
- Nginx com Certbot para SSL
- Imagem `cameras-app:latest` buildada

---

## Adicionar novo cliente

### 1. Apontar DNS
No painel do domínio, criar registro A:
```
cameras.cliente.com.br  →  A  →  49.13.237.163
```
Aguardar propagação (5–30 min).

### 2. Rodar o script
```bash
cd /var/www/cameras
./novo-cliente.sh \
  --slug nome-cliente \
  --domain cameras.nome-cliente.com.br \
  --email admin@nome-cliente.com.br \
  --password SenhaSegura123
```

O script faz automaticamente:
- Cria container isolado (app + MySQL) em porta auto-alocada
- Configura vhost Nginx
- Obtém certificado SSL via Certbot
- Salva credenciais em `/opt/cameras/<slug>/credenciais.txt`

### 3. Criar admin (se necessário)
Se o login não funcionar com as credenciais do script:
```bash
docker exec cameras_<slug>_app php /var/www/html/artisan tinker --execute="
App\Models\User::updateOrCreate(
    ['email' => 'admin@cliente.com.br'],
    ['name' => 'Administrador', 'password' => bcrypt('SenhaSegura123'), 'role' => 'admin']
);
echo 'OK';
"
```

---

## Atualizar o sistema (novo deploy)

Após `git push` do código:

```bash
cd /var/www/cameras

# 1. Baixar código novo
git pull

# 2. Rebuildar imagem
./build-image.sh

# 3. Recriar todos os containers com nova imagem
./atualizar-clientes.sh
```

> O `atualizar-clientes.sh` reinicia os containers sequencialmente — cada cliente fica fora por ~15 segundos durante o restart.

---

## Comandos úteis

### Listar todos os clientes e status
```bash
./listar-clientes.sh
```

### Ver logs de um cliente
```bash
docker logs -f cameras_<slug>_app
```

### Reiniciar um cliente
```bash
cd /opt/cameras/<slug>
docker compose restart app
```

### Sincronizar câmeras com go2rtc (após cadastrar câmeras no admin)
```bash
docker exec cameras_<slug>_app php /var/www/html/artisan go2rtc:sync
```

### Acessar banco de dados de um cliente
```bash
docker exec -it cameras_<slug>_db mysql -uroot -p
# senha: ver /opt/cameras/<slug>/credenciais.txt
```

### Ver credenciais de um cliente
```bash
cat /opt/cameras/<slug>/credenciais.txt
```

---

## Remover um cliente

```bash
# Remove containers e Nginx, mantém dados (volumes)
./remover-cliente.sh --slug nome-cliente

# Remove tudo incluindo banco de dados e arquivos
./remover-cliente.sh --slug nome-cliente --apagar-dados
```

---

## Rebuild manual da imagem

Necessário quando há mudanças em:
- `Dockerfile`
- `docker/` (nginx, supervisor, entrypoint)
- Dependências PHP (`composer.json`) ou JS (`package.json`)

```bash
cd /var/www/cameras
git pull
./build-image.sh
./atualizar-clientes.sh
```

> Para mudanças apenas em código PHP/Blade (sem novas dependências), o rebuild também é necessário pois o código está dentro da imagem.

---

## Troubleshooting

### Container não sobe — aguardando MySQL
```bash
docker logs cameras_<slug>_db
# Se der erro de autenticação:
docker exec cameras_<slug>_db mysql -uroot -p$(docker exec cameras_<slug>_db printenv MYSQL_ROOT_PASSWORD) \
  -e "ALTER USER '$(docker exec cameras_<slug>_db printenv MYSQL_USER)'@'%' IDENTIFIED WITH mysql_native_password BY '$(docker exec cameras_<slug>_db printenv MYSQL_PASSWORD)';"
docker restart cameras_<slug>_app
```

### WebRTC não funciona (câmeras sem imagem)
```bash
# Verificar se streams estão cadastrados
docker exec cameras_<slug>_app wget -qO- http://127.0.0.1:1984/api/streams

# Se vazio, sincronizar manualmente
docker exec cameras_<slug>_app php /var/www/html/artisan go2rtc:sync
```

### 502 Bad Gateway
```bash
# Verificar se container está rodando
docker ps | grep <slug>

# Ver logs
docker logs --tail 50 cameras_<slug>_app
```

### SSL falhou durante novo-cliente.sh
```bash
# Rodar certbot manualmente após DNS propagar
certbot certonly --nginx -d cameras.cliente.com.br

# Depois recriar o vhost HTTPS manualmente ou rodar:
./novo-cliente.sh --slug <slug> --domain <domain> --email <email> --password <senha>
# (o script detecta que o container já existe e só refaz o SSL/Nginx)
```

---

## Capacidade estimada (Hetzner CX32 — 4 vCPU / 8GB RAM)

| Recurso | Por cliente idle | Limite prático |
|---|---|---|
| RAM | ~500MB (app + MySQL) | 10–15 clientes |
| CPU | < 5% | Ilimitado em idle |
| CPU (stream ativo) | ~25% por stream | 3–4 streams simultâneos |
| Disco | ~200MB + dados | 80GB total no servidor |

Para escalar além de 15 clientes ou 4 streams simultâneos: adicionar outro servidor Hetzner.

 chmod +x build-image.sh novo-cliente.sh atualizar-clientes.sh listar-clientes.sh remover-cliente.sh
