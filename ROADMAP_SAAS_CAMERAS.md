# Sistema de Câmeras SaaS — Roadmap de Produto e Contexto Técnico

## Contexto do Projeto

Sistema web SaaS para academias, escolas esportivas e estabelecimentos que precisam disponibilizar acesso a câmeras de segurança para alunos/clientes de forma controlada, com assinatura e auditoria completa.

**Stack:** Laravel 13.11.2 · PHP 8.3 · MySQL 8.4 · Tailwind CSS · Alpine.js · go2rtc (RTSP → WebRTC) · FFmpeg  
**Infraestrutura:** Docker · Multi-tenant via script de provisionamento · Laragon (dev local Windows)  
**Câmeras suportadas:** Intelbras/Dahua (protocolo Dahua), RTSP genérico, HLS

O sistema **já funciona** em produção. O objetivo agora é **evoluir como produto SaaS comercializável**, com features que justifiquem assinatura recorrente e reduzam churn.

---

## O que já está implementado

### Admin
- CRUD de câmeras (IP, porta, usuário/senha, canal, subtype)
- CRUD de usuários com quota de clipes configurável (100/300/500/800 MB)
- Controle de acesso por câmera com expiração opcional (`camera_user` pivot)
- Assinaturas: criar (mensal/trimestral/anual), renovar, suspender
- Dashboard: online agora, assinaturas vencendo em 7 dias, atividade recente, acessos negados
- Logs de acesso com filtro por usuário/evento/data
- Toggle de gravação contínua por câmera via go2rtc
- Playback de DVR (Intelbras/Dahua) via RTSP com go2rtc

### Cliente
- Dashboard com câmeras ao vivo (WebRTC via go2rtc)
- Grade configurável (1/2/3 colunas)
- Botões mobile: Expandir + Gravações
- Playback de gravações do DVR
- Criação de clipes com controle de quota por usuário
- Download de clipes
- Página de assinatura expirada

### Infraestrutura
- Heartbeat a cada 30s (atualiza `active_sessions`)
- Scheduler: `clips:purge` (2 dias) + `subscriptions:expire` (00:05)
- Proxy WebRTC para evitar CORS
- Multi-tenant via Docker + script de provisionamento

### Banco de dados relevante
- `users` (role: admin|client, clips_quota_mb)
- `cameras` (stream_url, ip, port, channel, subtype, is_active, is_recording)
- `camera_user` (granted_at, expires_at)
- `subscriptions` (plan, status, starts_at, expires_at, granted_by, notes)
- `access_logs` (event enum: login/logout/stream_start/stream_stop/access_denied/subscription_expired)
- `active_sessions` (watching_camera_id, last_seen_at via heartbeat 30s)
- `recordings`, `recording_segments`, `clips`

---

## Roadmap de Features por Prioridade Comercial

As features estão agrupadas por impacto em vendas e retenção. Implemente na ordem sugerida.

---

### PRIORIDADE 1 — Produto vendável hoje (quick wins)

#### 1.1 Self-service de assinatura com pagamento integrado

**Por quê vende:** O admin hoje cria assinatura manualmente com nota "Pagamento PIX". Isso não escala para dezenas de clientes.

**O que implementar:**
- Integração com **Stripe** (internacional) ou **Pagar.me / Asaas** (BR — PIX + boleto + cartão)
- Fluxo: cliente acessa `/assinar`, escolhe plano, paga, assinatura criada automaticamente
- Webhook de pagamento → atualiza `subscriptions.status`
- Renovação automática com cartão salvo
- E-mail de confirmação de pagamento (já tem Laravel Mail)
- Página `/minha-assinatura` para o cliente ver status, próximo vencimento, histórico de pagamentos

**Tabelas novas:**
```sql
payment_transactions (id, subscription_id, gateway, gateway_id, amount, status, paid_at, meta json)
```

**Impacto:** Remove trabalho manual do admin e permite escalar sem aumentar equipe.

---

#### 1.2 E-mail automatizado para o cliente

**Por quê vende:** Profissionaliza o produto. Cliente se sente cuidado.

**O que implementar (Mailable + Queue):**
- Boas-vindas ao criar conta
- Assinatura ativada (com link direto para câmeras)
- Aviso 7 dias antes do vencimento
- Aviso 1 dia antes do vencimento
- Assinatura expirada (com link para renovar)
- Acesso concedido a nova câmera
- Clipe pronto para download

**Config:** `MAIL_MAILER=smtp` já suportado pelo Laravel. Usar **Resend** ou **Mailgun** para deliverability.

---

#### 1.3 Notificações push / WhatsApp

**Por quê vende:** Brasil usa WhatsApp. Academias têm relacionamento via WhatsApp com alunos.

**O que implementar:**
- Campo `whatsapp` na tabela `users`
- Integração com **Evolution API v2** (já na infraestrutura do TRSystem)
- Disparar mensagem nos mesmos eventos do e-mail acima
- Admin pode ativar/desativar por tenant

---

#### 1.4 Página de login personalizada por tenant (white-label básico)

**Por quê vende:** A academia quer mostrar a marca dela, não a sua.

**O que implementar:**
- Tabela `tenant_settings` (logo_url, primary_color, accent_color, company_name, favicon_url)
- Middleware que carrega config do tenant pelo domínio/subdomínio
- Variáveis CSS injetadas no `<head>` via Blade
- Tela de login, dashboard e e-mails com logo/cor da academia
- Upload de logo no painel admin

---

### PRIORIDADE 2 — Diferencial competitivo (próximas 4–8 semanas)

#### 2.1 Alertas de movimento / eventos por câmera

**Por quê vende:** A grande dor do mercado não é ver a câmera — é **ser avisado quando algo acontece**.

**O que implementar (fase 1 — sem IA):**
- go2rtc tem suporte a hooks de motion detection via `onvif` ou integração com câmeras que suportam eventos ONVIF
- Tabela `camera_events` (camera_id, event_type: motion|tampering|offline, detected_at, snapshot_url, notified_at)
- Worker que consome eventos do go2rtc/câmera e insere na tabela
- Notificação push/e-mail/WhatsApp para o admin quando detectado
- Dashboard admin: timeline de eventos por câmera

**O que implementar (fase 2 — com IA):**
- Capturar frame via FFmpeg quando evento detectado
- Enviar para API de visão (OpenAI Vision ou Rekognition) para classificar: "pessoa", "veículo", "animal"
- Filtrar falsos positivos (folha voando, sombra)
- Cliente recebe alerta com thumbnail do evento

---

#### 2.2 Snapshot agendado e relatório de presença

**Por quê vende:** Academias precisam provar para seguros/responsáveis que o ambiente estava monitorado.

**O que implementar:**
- Comando Artisan `cameras:snapshot {camera_id}` via FFmpeg (captura frame RTSP → salva JPG)
- Scheduler: snapshot a cada X minutos por câmera (configurável no admin)
- Tabela `snapshots` (camera_id, captured_at, file_path, file_size)
- Galeria de snapshots no admin por câmera + filtro por data
- Relatório PDF exportável: "Câmera X — capturas entre data A e data B"

---

#### 2.3 App mobile (PWA primeiro, depois nativo)

**Por quê vende:** Cliente quer ver câmera no celular de forma nativa, sem lembrar de URL.

**Fase 1 — PWA (0 custo adicional):**
- Adicionar `manifest.json` com ícone, nome, `display: standalone`
- Service Worker básico para cache de assets
- Meta tags para iOS (apple-touch-icon, apple-mobile-web-app-capable)
- Botão "Adicionar à tela inicial" no primeiro acesso mobile

**Fase 2 — App nativo (Flutter ou React Native):**
- Reutiliza toda a API REST já existente
- Push notifications nativas (FCM)
- Biometria para login

---

#### 2.4 Dashboard de analytics para o admin do tenant

**Por quê vende:** O gestor da academia quer saber quem está acessando, quando e por quanto tempo.

**O que implementar (usando `access_logs` + `active_sessions` já existentes):**
- Horas de streaming por câmera por mês
- Ranking de câmeras mais assistidas
- Horário de pico de acesso (heatmap por hora/dia)
- Tempo médio de sessão por usuário
- Taxa de renovação de assinaturas
- Gráfico de receita mensal (se tiver pagamento integrado)
- Exportar relatório CSV/PDF

---

#### 2.5 Multi-câmera / mosaico com layout salvo

**Por quê vende:** Segurança profissional exige ver múltiplas câmeras ao mesmo tempo.

**O que implementar:**
- Tela de mosaico full-screen: 2x2, 3x3, 1+5
- Salvar layout preferido por usuário (localStorage + sync DB)
- Modo apresentação: rotação automática entre câmeras a cada N segundos (configurável)
- Botão "Mosaico" no dashboard do cliente

---

### PRIORIDADE 3 — Features premium (diferencial de plano alto)

#### 3.1 Gravação na nuvem por câmera (cloud recording)

**Por quê vende:** DVR queima, rouba, falha. Gravação na nuvem é o argumento de venda mais forte para segurança.

**O que implementar:**
- Usar go2rtc + FFmpeg para gravar segmentos HLS (.m3u8 + .ts) no servidor
- Upload automático para **S3 / Backblaze B2 / Wasabi** (mais barato)
- Retenção configurável por plano: 7 dias / 15 dias / 30 dias
- Player de playback cloud integrado no sistema
- Tabela `cloud_segments` (camera_id, bucket, path, started_at, ended_at, size_bytes, uploaded)
- Comando `recordings:upload-pending` no scheduler

**Planos sugeridos:**
- Basic: sem cloud recording
- Pro: 7 dias por câmera
- Enterprise: 30 dias, todas as câmeras

---

#### 3.2 Reconhecimento facial (acesso por face)

**Por quê vende:** Academias adoram biometria. É o diferencial mais "wow" para demonstração.

**O que implementar:**
- Câmera dedicada na entrada
- Cliente cadastra foto no perfil
- Backend processa frame via **AWS Rekognition** ou **Face++ API**
- Match → registra entrada/saída automaticamente
- Relatório de presença gerado automaticamente
- Integrar com sistema de catraca (webhook para GPIO/relé)

---

#### 3.3 Portal do responsável (para academias infantis / escolinhas)

**Por quê vende:** Pais pagam premium para ver filhos em segurança. Caso de uso com altíssima percepção de valor.

**O que implementar:**
- Role `guardian` (responsável) além de `client`
- Responsável vinculado a aluno(s)
- Câmeras liberadas automaticamente baseadas nas turmas do aluno
- Notificação quando aluno é detectado chegando/saindo (via reconhecimento facial ou QR code)
- Acesso apenas nos horários de aula (restricao por `camera_user.expires_at` dinâmico)

---

#### 3.4 Integração com sistema de controle de acesso

**Por quê vende:** Academias já têm catraca/controle de acesso. Integrar = vender junto.

**O que implementar:**
- Webhook receiver para eventos de entrada/saída de catraca
- Correlacionar com câmera mais próxima → salvar clip automático dos 10 segundos do evento
- Timeline de presença com thumbnail
- API REST para sistemas de terceiros consumirem eventos de câmera

---

### PRIORIDADE 4 — SaaS B2B (escalar para múltiplos tenants)

#### 4.1 Painel Super-Admin (sua visão de todos os tenants)

**O que implementar:**
- Rota `/superadmin` separada com middleware próprio
- Listar todos os tenants: nome, câmeras ativas, usuários, status de pagamento, uso de storage
- Criar novo tenant (executa o script Docker atual via `exec`)
- Suspender/reativar tenant
- Ver logs de todos os tenants
- Dashboard de MRR (Monthly Recurring Revenue)

---

#### 4.2 Planos com limites configuráveis

**O que implementar:**
- Tabela `plans` (name, max_cameras, max_users, storage_gb, recording_days, price_monthly, price_annual)
- Tenant vinculado a um plano
- Enforcement via middleware: ao adicionar câmera além do limite → erro com upgrade sugerido
- Página de upgrade de plano self-service

**Planos sugeridos para o mercado:**
| Plano | Câmeras | Usuários | Gravação | Preço/mês |
|---|---|---|---|---|
| Starter | 4 | 10 | Sem cloud | R$ 97 |
| Pro | 16 | 50 | 7 dias | R$ 297 |
| Enterprise | Ilimitado | Ilimitado | 30 dias | R$ 897 |

---

#### 4.3 API pública para integradores

**O que implementar:**
- Autenticação via API Key (tabela `api_keys`)
- Endpoints REST documentados (Swagger/Scramble):
  - `GET /api/cameras` — lista câmeras com status online/offline
  - `GET /api/cameras/{id}/snapshot` — retorna JPEG ao vivo
  - `POST /api/cameras/{id}/clip` — cria clipe de X segundos
  - `GET /api/users/{id}/sessions` — histórico de acesso
- Rate limiting por API key
- Uso: integradores, sistemas de condomínio, apps terceiros

---

## Observações Técnicas para Implementação

### Câmeras Intelbras
- Protocolo: Dahua
- RTSP: `rtsp://user:pass@ip:554/cam/realmonitor?channel=1&subtype=0`
- DVR API: porta HTTP 8000 (mediaFileFind)
- DDNS: `villatenis.ddns-intelbras.com.br`

### go2rtc
- Roda em `http://localhost:1984`
- Config em `config/cameras.php` → `go2rtc_public_url`
- Streams nomeados por `cam{id}`
- Playback DVR: stream temporário nomeado por timestamp
- Proxy WebRTC em `/go2rtc/webrtc` para evitar CORS

### Ambiente de desenvolvimento
- PHP: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe` (nunca XAMPP PHP 7.4)
- Projeto: `C:\laragon\www\cameras`
- URL local: `http://cameras.test`
- Banco: MySQL `cameras` (root sem senha)

### Controle de acesso (ordem de verificação)
1. Middleware `auth` — autenticado?
2. Middleware `subscription` — assinatura ativa? → redireciona `/assinatura-expirada`
3. Controller/Model — câmera liberada? (`camera_user` + `expires_at`)
4. Pivot `expires_at` — acesso da câmera não expirou?

### Scheduler atual
```
* * * * * cd /var/www/cameras && php artisan schedule:run
```
- `clips:purge` — 00:00 (apaga clipes +2 dias)
- `subscriptions:expire` — 00:05 (marca vencidas)

### Multi-tenant
- Cada tenant roda em container Docker separado
- Provisionamento via script (já funcional)
- Banco isolado por tenant

---

## Sugestão de Ordem de Implementação (Sprint)

| Sprint | Feature | Impacto |
|---|---|---|
| 1 | E-mails automatizados (boas-vindas, vencimento) | Profissionaliza imediatamente |
| 1 | White-label básico (logo + cor por tenant) | Facilita venda para academias |
| 2 | Self-service de assinatura com Asaas/PIX | Elimina trabalho manual do admin |
| 2 | PWA mobile (manifest + ícone) | Experiência mobile sem custo |
| 3 | Snapshot agendado + galeria | Feature exclusiva percebida como valiosa |
| 3 | Mosaico multi-câmera com layout salvo | Diferencial para clientes power user |
| 4 | Dashboard analytics do tenant | Justifica renovação de assinatura |
| 4 | Painel Super-Admin | Permite gerenciar múltiplos clientes sem SSH |
| 5 | Alertas de movimento (fase 1 sem IA) | Principal dor do mercado |
| 6 | Cloud recording (S3 + retenção por plano) | Feature premium que justifica plano caro |
| 7 | Planos com limites + upgrade self-service | Monetização escalável |
