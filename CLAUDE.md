# Sistema de Câmeras de Segurança — Contexto do Projeto

## Objetivo
Sistema web para gerenciar câmeras de segurança e disponibilizar acesso a alunos/clientes
através de um painel com login. Construído em **Laravel 11** com layout moderno.

## Funcionalidades planejadas
| Módulo | Descrição |
|---|---|
| Autenticação | Login de admin + painel separado para alunos/clientes |
| Câmeras | CRUD completo (nome, local, URL do stream, status ativo/inativo) |
| Ao vivo | Player de stream em tempo real (RTSP via HLS) |
| Gravações | Upload, listagem e reprodução de vídeos gravados |
| Acessos | Admin controla quais câmeras cada aluno/cliente pode ver |
| Layout | Tailwind CSS + Alpine.js, visual moderno e responsivo |

## Stack do projeto
- **PHP** 8.3.30 (Laragon — `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`)
- **Laravel** 11 (Laravel 13 na prática — v13.11.2)
- **MySQL** 8.4.3 (Laragon)
- **Breeze** v2.4.2 — autenticação (em instalação)
- **Tailwind CSS** + **Alpine.js** — frontend
- **Vite** — build de assets

## Ambiente
- Projeto: `C:\laragon\www\cameras`
- URL local: `http://cameras.test` (configurar no Laragon > Hosts)
- Banco de dados: `cameras` (MySQL, root sem senha)
- PHP no PATH do Laragon: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`

## .env configurado
```
APP_NAME="Sistema de Câmeras"
APP_URL=http://cameras.test
APP_LOCALE=pt_BR
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cameras
DB_USERNAME=root
DB_PASSWORD=
```

## Status atual (em 21/05/2026)
- [x] Projeto Laravel criado em `C:\laragon\www\cameras`
- [x] .env configurado (MySQL, locale pt_BR, URL cameras.test)
- [x] ZIP extension habilitada no php.ini do Laragon
- [ ] `laravel/breeze` instalando (composer require em background — aguardar concluir)
- [ ] `php artisan breeze:install blade` — scaffold de auth
- [ ] Criar banco `cameras` no MySQL
- [ ] `php artisan migrate`
- [ ] Criar migrations customizadas (cameras, recordings, camera_user)
- [ ] Models, Controllers, Policies
- [ ] Views: layout, dashboard admin, painel aluno, câmeras, gravações

## Próximos passos ao retomar
Usar sempre o PHP do Laragon para artisan e composer:

```powershell
$php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$composer = "C:\laragon\bin\composer\composer.phar"
Set-Location "C:\laragon\www\cameras"

# Verificar se Breeze terminou de instalar
& $php artisan list

# Instalar scaffold do Breeze (blade + Tailwind)
& $php artisan breeze:install blade

# Criar banco no MySQL (Laragon precisa estar rodando)
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS cameras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Rodar migrations
& $php artisan migrate

# Instalar dependências JS e buildar
npm install
npm run build
```

## Estrutura de banco planejada

### Tabela `cameras`
| Campo | Tipo | Descrição |
|---|---|---|
| id | bigint PK | |
| name | string | Nome da câmera |
| location | string | Localização/descrição |
| stream_url | string | URL do stream (RTSP, HLS, etc) |
| is_active | boolean | Ativa/inativa |
| created_at / updated_at | timestamp | |

### Tabela `recordings`
| Campo | Tipo | Descrição |
|---|---|---|
| id | bigint PK | |
| camera_id | FK cameras | |
| title | string | Título da gravação |
| filename | string | Nome do arquivo em storage |
| duration | integer | Duração em segundos |
| recorded_at | datetime | Data/hora da gravação |

### Tabela `camera_user` (pivot — controle de acesso)
| Campo | Tipo | Descrição |
|---|---|---|
| camera_id | FK cameras | |
| user_id | FK users | |
| granted_at | timestamp | Quando o acesso foi dado |

### Tabela `users` (padrão Laravel + campo role)
| Campo | Tipo | Descrição |
|---|---|---|
| role | enum | `admin` ou `client` |

## Perfis de usuário
- **admin** — acesso total: gerenciar câmeras, gravações, usuários e acessos
- **client** — vê apenas as câmeras liberadas pelo admin

## Observações importantes
- O projeto antigo estava em `C:\projetos\cameras` (PHP puro) — foi descartado
- Laragon foi instalado em 21/05/2026 especificamente para este projeto
- A sessão de chat foi iniciada em `c:\projetos\cameras` mas o projeto real é `C:\laragon\www\cameras`
- Usar sempre o PHP do Laragon, nunca o do XAMPP (`C:\xampp\php\php.exe` = PHP 7.4, incompatível)
