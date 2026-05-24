# Sistema de Câmeras de Segurança — Documentação do Projeto

## Visão geral
Sistema web SaaS para academias, escolas esportivas e estabelecimentos que precisam disponibilizar
acesso a câmeras de segurança para alunos/clientes de forma controlada, com assinatura e auditoria completa.

Construído em **Laravel 13** (v13.11.2), **Tailwind CSS**, **Alpine.js** e integração com **go2rtc** para streaming RTSP via WebRTC.

---

## Stack

| Componente | Versão / Detalhe |
|---|---|
| PHP | 8.3.30 (Laragon) |
| Laravel | 13.11.2 |
| MySQL | 8.4.3 |
| Breeze | 2.4.2 (autenticação) |
| Tailwind CSS + Alpine.js | frontend |
| Vite | build de assets |
| go2rtc | proxy RTSP → WebRTC |
| ffmpeg | transcodificação de gravações |

---

## Ambiente local

- **Projeto:** `C:\laragon\www\cameras`
- **URL local:** `http://cameras.test`
- **PHP:** `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`
- **Banco:** MySQL `cameras` (root sem senha)
- **go2rtc:** `http://localhost:1984`

### Comandos artisan (sempre usar o PHP do Laragon)

```powershell
$php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
Set-Location "C:\laragon\www\cameras"
& $php artisan migrate
& $php artisan schedule:run
```

### .env principais

```
APP_NAME="Sistema de Câmeras"
APP_URL=http://cameras.test
APP_LOCALE=pt_BR
DB_CONNECTION=mysql
DB_DATABASE=cameras
DB_USERNAME=root
DB_PASSWORD=
```

---

## Banco de dados

### `users`
| Campo | Tipo | Descrição |
|---|---|---|
| role | enum | `admin` \| `client` |
| clips_quota_mb | int | Quota de storage de clipes (100/300/500/800 MB) |

### `cameras`
| Campo | Tipo | Descrição |
|---|---|---|
| name | string | Nome da câmera |
| location | string | Localização |
| stream_url | string | URL manual (HLS/RTSP) |
| ip | string | IP da câmera/NVR |
| port | int | Porta RTSP (padrão 554) |
| http_port | int | Porta HTTP do NVR |
| cam_username | string | Usuário da câmera |
| cam_password | string | Senha da câmera |
| channel | int | Canal do NVR |
| subtype | int | 0 = main stream, 1 = substream |
| is_active | boolean | Câmera ativa/inativa |
| is_recording | boolean | Gravação ativa via go2rtc |

### `camera_user` (pivot de acesso)
| Campo | Tipo | Descrição |
|---|---|---|
| camera_id | FK | |
| user_id | FK | |
| granted_at | timestamp | Quando o acesso foi dado |
| expires_at | timestamp | Expiração do acesso (null = permanente) |

### `subscriptions`
| Campo | Tipo | Descrição |
|---|---|---|
| user_id | FK | |
| plan | enum | `monthly` \| `quarterly` \| `annual` |
| status | enum | `active` \| `suspended` \| `expired` \| `cancelled` |
| starts_at | timestamp | |
| expires_at | timestamp | |
| granted_by | FK users | Admin que criou |
| notes | text | Observação (ex: "Pagamento PIX") |

### `access_logs`
| Campo | Tipo | Descrição |
|---|---|---|
| user_id | FK | |
| camera_id | FK nullable | |
| event | enum | `login` \| `logout` \| `stream_start` \| `stream_stop` \| `access_denied` \| `subscription_expired` |
| ip_address | string | |
| user_agent | string | |
| meta | json | Dados extras |

### `active_sessions`
| Campo | Tipo | Descrição |
|---|---|---|
| user_id | FK unique | Um registro por usuário |
| ip_address | string | |
| watching_camera_id | FK nullable | Câmera sendo assistida agora |
| logged_in_at | timestamp | |
| last_seen_at | timestamp | Atualizado pelo heartbeat a cada 30s |

### `recordings`
Gravações manuais (upload). Separado dos segmentos do go2rtc.

### `recording_segments`
Segmentos gerados automaticamente pelo go2rtc durante gravação contínua.

### `clips`
Clipes criados pelos clientes a partir do playback do DVR.
Cada clipe tem `file_size`, `status` (pending/processing/ready/error) e respeita a `clips_quota_mb` do usuário.

---

## Perfis de usuário

| Perfil | Acesso |
|---|---|
| `admin` | Total: câmeras, gravações, usuários, assinaturas, logs |
| `client` | Somente câmeras liberadas + assinatura ativa |

---

## Controle de acesso em camadas

Ao acessar qualquer rota de cliente, o sistema verifica na ordem:

1. **Autenticado?** → middleware `auth`
2. **Assinatura ativa?** → middleware `subscription` (redireciona para `/assinatura-expirada`)
3. **Câmera liberada?** → verificação no controller/model (`camera_user` + `expires_at`)
4. **Acesso da câmera não expirou?** → `expires_at` do pivot

---

## Funcionalidades implementadas

### Admin
- CRUD de câmeras (com IP, porta, usuário/senha, canal, subtype)
- CRUD de usuários com quota de clipes configurável
- Controle de acesso por câmera com expiração opcional
- Assinaturas: criar (mensal/trimestral/anual), renovar, suspender
- Dashboard com: online agora, assinaturas vencendo em 7 dias, atividade recente, acessos negados
- Logs de acesso com filtro por usuário/evento/data
- Toggle de gravação contínua por câmera (via go2rtc)
- Playback de DVR (Intelbras/Dahua) via RTSP com go2rtc

### Cliente
- Dashboard com câmeras ao vivo (WebRTC via go2rtc)
- Grade configurável (1/2/3 colunas)
- Botões sempre visíveis no mobile (Expandir + Gravações)
- Playback de gravações do DVR
- Criação de clipes com controle de quota por usuário
- Download de clipes
- Página de assinatura expirada com mensagem clara

---

## Scheduler (cron)

```
* * * * * cd /var/www/cameras && php artisan schedule:run >> /dev/null 2>&1
```

Tarefas agendadas:
- `clips:purge` — apaga clipes com mais de 2 dias (00:00)
- `subscriptions:expire` — marca assinaturas vencidas como `expired` (00:05)

---

## Heartbeat

Todos os layouts enviam `POST /heartbeat` a cada 30 segundos.
Atualiza `active_sessions.last_seen_at` e `watching_camera_id`.
Usuário é considerado online se `last_seen_at >= now - 2 minutos`.

---

## Integração go2rtc

- go2rtc roda em `http://localhost:1984`
- URL pública configurada em `config/cameras.php` → `go2rtc_public_url`
- O browser faz WebRTC direto com go2rtc (evita CORS via proxy em `/go2rtc/webrtc`)
- Streams nomeados por `cam{id}` (ex: `cam1`, `cam2`)
- Playback DVR: stream temporário nomeado por timestamp

---

## Estrutura de controllers

```
app/Http/Controllers/
├── Admin/
│   ├── DashboardController    — painel admin com métricas
│   ├── CameraController       — CRUD câmeras
│   ├── UserController         — CRUD usuários + grant/revoke/quota
│   ├── SubscriptionController — criar/renovar/suspender assinaturas
│   ├── AccessLogController    — listagem de logs com filtros
│   ├── RecordingController    — gravações manuais
│   ├── SegmentController      — segmentos go2rtc
│   └── PlaybackController     — playback DVR
├── Client/
│   ├── DashboardController    — câmeras do cliente
│   ├── LiveController         — tela expandida ao vivo
│   └── ClipController         — clipes: criar/listar/download/apagar
├── Auth/                      — gerado pelo Breeze
├── HeartbeatController        — POST /heartbeat
└── Go2rtcProxyController      — proxy WebRTC
```

---

## Observações importantes

- Usar sempre o PHP do Laragon (`C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`), nunca o XAMPP (PHP 7.4)
- Câmeras Intelbras: protocolo Dahua — RTSP em `rtsp://user:pass@ip:554/cam/realmonitor?channel=1&subtype=0`
- DVR Intelbras usa porta HTTP 8000 para API CGI (mediaFileFind)
- DDNS do NVR: `villatenis.ddns-intelbras.com.br`
