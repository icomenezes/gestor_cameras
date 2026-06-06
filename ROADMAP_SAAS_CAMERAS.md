# CâmerasSaaS — Documentação Completa do Produto

## Tagline
**Acesso profissional às câmeras da sua academia — com gravação, playback e controle total, sem precisar de técnico.**

## Proposta de Valor
Sistema web SaaS que permite academias, escolinhas esportivas e estabelecimentos disponibilizar acesso às câmeras de segurança para alunos e clientes de forma controlada, com assinatura recorrente, gravações no DVR, clipes para download e auditoria completa de acessos.

O cliente acessa pelo celular ou computador, via link, sem instalar nada. O gestor da academia tem controle total de quem vê o quê e por quanto tempo.

---

## Público-Alvo

- **Academias de esportes** (natação, ginástica, futebol, tênis, musculação)
- **Escolinhas infantis e recreativas**
- **Condomínios e espaços esportivos privados**
- **Qualquer estabelecimento** que queira monetizar o acesso às câmeras de segurança já instaladas

---

## O que o cliente precisa ter

- DVR/NVR Intelbras ou Dahua com acesso à internet (porta RTSP aberta)
- Câmeras IP com suporte RTSP (padrão do mercado)
- Também funciona com qualquer câmera que suporte streaming RTSP genérico ou HLS

---

## Câmeras e DVRs Suportados

| Marca | Protocolo | Observação |
|---|---|---|
| Intelbras | Dahua (nativo) | MHDX, iMHDX, NVD — porta 8000 (HTTP API) + 554 (RTSP) |
| Dahua | Dahua | Totalmente compatível |
| Hikvision | RTSP genérico | Funciona via URL RTSP manual |
| Genérico | RTSP / HLS | Qualquer câmera com URL de stream |

---

## Funcionalidades Implementadas (Disponíveis Agora)

### Para o Administrador da Academia

#### Gestão de Câmeras
- Cadastro de câmeras com IP, porta, usuário, senha, canal e substream
- Toggle de gravação contínua por câmera (via go2rtc)
- Suporte a múltiplos canais do mesmo DVR/NVR
- Visualização ao vivo das câmeras no painel admin

#### Gestão de Usuários e Assinaturas
- Cadastro de clientes com controle de quota de armazenamento (100 / 300 / 500 / 800 MB por usuário)
- Criação de assinaturas mensal, trimestral e anual
- Renovação e suspensão de assinaturas
- Liberação de câmeras por usuário com expiração opcional (ex: "acesso até 31/12")
- Revogação de acesso por câmera individualmente

#### Dashboard em Tempo Real
- Clientes online agora (com câmera que estão assistindo)
- Assinaturas vencendo nos próximos 7 dias
- Atividade recente de acessos
- Acessos negados (tentativas bloqueadas)

#### Playback de DVR
- Timeline visual do dia completo em blocos de 5 minutos
- Navegação por data
- Reprodução via WebRTC (sem plugin, direto no browser)
- Controle de velocidade de reprodução: 1x, 2x, 4x
- Gravar cenas durante o playback (o sistema detecta o instante exato no DVR)
- Criar clipes de trechos específicos (1 / 5 / 10 minutos)

#### Alertas e Eventos de Câmera
- Webhook para receber eventos de câmeras: movimento, adulteração, câmera offline/online
- Notificação por e-mail ao admin quando evento detectado
- Notificação por WhatsApp ao admin quando evento detectado
- Timeline de eventos por câmera com filtro por tipo e data

#### Logs e Auditoria
- Log completo de acessos: login, logout, início/fim de stream, acesso negado, assinatura expirada
- Filtro por usuário, evento e data
- Rastreabilidade total de quem acessou qual câmera e quando

#### Analytics do Tenant
- Câmeras mais assistidas no mês
- Heatmap de horários de pico (hora × dia da semana nos últimos 30 dias)
- Tempo médio de sessão por usuário
- Taxa de renovação de assinaturas
- Gráfico de logins diários (últimos 30 dias)
- Totais: clientes ativos, assinaturas ativas, câmeras, streams no mês

#### Snapshots Agendados
- Captura automática de frame por câmera em intervalo configurável
- Galeria de snapshots por câmera com filtro por data
- Histórico visual de monitoramento

#### White-Label (Identidade Visual da Academia)
- Upload de logo da academia (PNG/JPG/SVG)
- Upload de favicon personalizado
- Cor primária e cor de destaque configuráveis (seletor hex)
- Nome da empresa exibido no sistema
- E-mail e WhatsApp de suporte da academia
- Login e dashboard exibem a marca da academia, não a marca do sistema

---

### Para o Cliente (Aluno / Responsável)

#### Câmeras ao Vivo
- Dashboard com todas as câmeras liberadas pelo admin
- Streaming WebRTC em tempo real (sem plugin, funciona no celular)
- Grade configurável: 1, 2 ou 3 câmeras por linha
- Botão para expandir câmera em tela cheia
- Mosaico multi-câmera: ver várias ao mesmo tempo

#### Playback de Gravações do DVR
- Acesso ao histórico de gravações da câmera
- Timeline visual do dia em blocos de 5 minutos
- Clique no bloco para assistir o trecho
- Controle de velocidade 1x / 2x / 4x para encontrar o momento certo
- Seleção de data para navegar no histórico

#### Clipes Pessoais
- Gravar cena durante o playback (botão sobre o vídeo)
- Criar clipes de trechos específicos (1 / 5 / 10 minutos) via formulário
- Biblioteca pessoal de clipes com status (processando / pronto / falhou)
- Preview do clipe antes de baixar (player inline)
- Download do clipe em MP4
- Barra de storage com uso atual vs. quota do plano
- Aviso automático que clipes são deletados após 2 dias

#### Assinatura
- Página de assinatura expirada com mensagem clara e link para contato
- Acesso bloqueado automaticamente ao vencer a assinatura

---

## Arquitetura e Infraestrutura

### Stack Técnico
| Componente | Tecnologia |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Frontend | Tailwind CSS + Alpine.js |
| Banco de dados | MySQL 8.4 |
| Streaming | go2rtc (RTSP → WebRTC) |
| Transcodificação | FFmpeg |
| Autenticação | Laravel Breeze |
| Deploy | Docker (um container por cliente) |

### Multi-Tenant Isolado
- Cada academia roda em seu próprio container Docker
- Banco de dados completamente separado por cliente
- Domínio próprio com SSL automático via Let's Encrypt
- Provisionamento em minutos via script automatizado

### Painel Super-Admin (Operador do SaaS)
- Visão de todos os tenants: status, câmeras, usuários, assinaturas
- Criar novo tenant diretamente pelo painel (executa provisionamento)
- Suspender e reativar tenants com um clique (para/inicia container)
- Sincronização de dados em tempo real

### Segurança e Controle de Acesso
Verificações em camada, nesta ordem:
1. Autenticado? (middleware `auth`)
2. Assinatura ativa? (middleware `subscription`)
3. Câmera liberada para este usuário? (tabela `camera_user`)
4. Acesso da câmera não expirou? (campo `expires_at` do pivot)

### Heartbeat e Sessões
- Cada cliente envia heartbeat a cada 30 segundos
- Admin vê em tempo real quem está online e qual câmera está assistindo
- Sessão considerada ativa se `last_seen_at >= agora − 2 minutos`

### Tarefas Agendadas
- `clips:purge` — apaga clipes com mais de 2 dias (todo dia às 00:00)
- `subscriptions:expire` — marca assinaturas vencidas como expiradas (todo dia às 00:05)

---

## Planos Sugeridos

| Plano | Câmeras | Usuários | Storage de clipes | Preço/mês |
|---|---|---|---|---|
| **Starter** | até 4 | até 10 | 100 MB/usuário | R$ 97 |
| **Pro** | até 16 | até 50 | 300 MB/usuário | R$ 297 |
| **Enterprise** | Ilimitado | Ilimitado | 800 MB/usuário | R$ 897 |

> Todos os planos incluem: streaming ao vivo, playback DVR, clipes, white-label, alertas de movimento, analytics e suporte.

---

## Roadmap — Features em Desenvolvimento

### SPRINT 1 — Próximas semanas

#### Self-service de assinatura com pagamento integrado
- Integração com **Asaas** (PIX + boleto + cartão de crédito — foco Brasil)
- Fluxo: cliente acessa `/assinar`, escolhe plano, paga → assinatura criada automaticamente
- Webhook de pagamento → atualiza status da assinatura em tempo real
- Renovação automática com cartão salvo
- Página `/minha-assinatura`: status, próximo vencimento, histórico de pagamentos
- **Impacto:** elimina trabalho manual do admin, permite crescer sem aumentar equipe

#### E-mails automatizados
- Boas-vindas ao criar conta
- Assinatura ativada (com link direto para câmeras)
- Aviso 7 dias antes do vencimento
- Aviso 1 dia antes do vencimento
- Assinatura expirada (com link para renovar)
- Acesso concedido a nova câmera
- Clipe pronto para download
- **Config:** Resend ou Mailgun para alta entregabilidade

#### Notificações WhatsApp para o cliente
- Mesmos eventos acima enviados via WhatsApp
- Campo WhatsApp no cadastro do cliente
- Admin pode ativar/desativar por tenant
- **Infraestrutura:** Evolution API v2 (já na stack do TRSystem)

---

### SPRINT 2

#### PWA Mobile (Progressive Web App)
- `manifest.json` com ícone, nome, `display: standalone`
- Botão "Adicionar à tela inicial" no primeiro acesso mobile
- Ícones para iOS e Android
- **Resultado:** app na tela do celular sem custo de publicação em loja

#### Auto-cadastro do cliente
- Tela de registro própria (fluxo completo: nome → e-mail → senha → escolher plano → pagar)
- Admin não precisa cadastrar manualmente
- Role `client` atribuída automaticamente ao registrar

---

### SPRINT 3

#### Gravação na nuvem por câmera (Cloud Recording)
- go2rtc + FFmpeg gravando segmentos HLS no servidor
- Upload automático para S3 / Backblaze B2 / Wasabi
- Retenção configurável por plano: 7 / 15 / 30 dias
- Player de playback cloud integrado
- **Diferencial:** DVR queima, rouba, falha — a nuvem não

#### Alertas de movimento com IA (fase 2)
- Captura de frame via FFmpeg no momento do evento
- Classificação via OpenAI Vision: "pessoa", "veículo", "animal"
- Filtragem de falsos positivos
- Cliente recebe alerta com thumbnail

---

### SPRINT 4

#### Planos com limites configuráveis
- Tabela `plans` com limites de câmeras, usuários, storage, retenção
- Enforcement via middleware (erro com sugestão de upgrade)
- Página de upgrade self-service
- **Resultado:** monetização escalável por tier

#### API pública para integradores
- Autenticação via API Key
- Endpoints: listar câmeras, snapshot ao vivo, criar clipe, histórico de sessões
- Rate limiting por chave
- Documentação Swagger/Scramble
- **Uso:** integradores, sistemas de condomínio, apps terceiros

#### Portal do responsável (academias infantis)
- Role `guardian` além de `client`
- Câmeras liberadas automaticamente pelas turmas do aluno
- Acesso restrito aos horários de aula
- **Caso de uso:** pais pagam premium para ver filhos com segurança

---

## Observações para o Time de Vendas

**Argumento principal:** O cliente já tem câmeras e DVR instalados. O sistema apenas adiciona acesso web profissional ao que já existe — sem trocar hardware.

**Tempo de implantação:** menos de 1 hora para subir um novo cliente (domínio + DNS + provisionamento).

**Diferencial técnico:** streaming WebRTC (sem plugins, funciona em qualquer browser moderno, incluindo iPhone), acesso isolado por cliente, marca da academia no sistema.

**Objeção comum — "posso usar o app do DVR gratuitamente":**
- Apps de DVR são feitos para técnicos, não para pais/alunos
- Sem controle de acesso individual
- Sem assinatura e cobrança recorrente gerenciada
- Sem clipes para download com quota por usuário
- Sem logs de auditoria
- Sem white-label com a marca da academia
