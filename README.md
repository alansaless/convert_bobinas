# Projeto: Captura de Leads via Webhook + Integração RD Station

Este projeto implementa um backend em PHP (puro), uma UI administrativa em HTML/CSS/JS (Bootstrap 5 + Leaflet) e integração com RD Station (CRM) para criação/atualização automática de contatos, além de APIs REST e uma fila simples de reprocessamento.

## Stack
- Backend: PHP (arquivos principais: `index.php`, `webhook.php`, `api.php`, `rd_client.php`)
- Banco: MySQL (`leads`, `rd_logs`)
- Frontend: HTML5 + CSS (Bootstrap 5) + JavaScript vanilla
- Mapas: Leaflet + OpenStreetMap

## Endpoints
- `POST /webhook/lead` (via rewrite para `webhook.php?path=lead`)
  - Header obrigatório: `X-Api-Key`
  - Valida payload mínimo (name ou website ou phone)
  - Deduplica por `phone`, `website` ou `email`
  - Salva/atualiza em `leads` e tenta criar/atualizar contato no RD Station
  - Em caso de erro (rate limit 429, 5xx), incrementa `rd_attempts` e registra em `rd_logs`

- API REST (`api.php`):
  - `GET /api/leads?page=&limit=&city=&has_whatsapp=&verified=&source=&q=`
  - `GET /api/lead/{id}`
  - `POST /api/lead/{id}/verify`
  - `POST /api/lead/{id}/update` (edição via modal)
  - `POST /api/lead/send` (array de `ids`)
  - `GET /api/stats`
  - `GET /api/export` (CSV com filtros)

## Segurança
- `X-Api-Key` validada no `webhook.php`
- PDO com prepared statements
- Variáveis sensíveis em `.env`
- CORS configurável via `CORS_ALLOWED_ORIGINS`

## Instalação (Local: XAMPP/LAMP)
1. Copie os arquivos para o diretório do servidor (`htdocs` no XAMPP).
2. Crie o banco usando `schema.sql`:
   - No MySQL: `mysql -u root -p < schema.sql` (ou via phpMyAdmin Import).
3. Crie `.env` a partir de `.env.template` e configure:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
   - `API_KEY` (para o webhook)
   - `RD_CLIENT_ID`, `RD_CLIENT_SECRET`, `RD_REDIRECT_URI`, `RD_REFRESH_TOKEN`
   - Opcional: `CORS_ALLOWED_ORIGINS`, `RD_MAX_ATTEMPTS`, `RD_ALLOW_SYNTHETIC_EMAIL`
4. Garanta que o Apache tenha `mod_rewrite` habilitado para `.htaccess` funcionar.
5. Acesse `http://localhost/` para abrir a UI.

## RD Station: criando App e obtendo tokens
1. Crie um app em sua conta RD Station Marketing (consulte a documentação oficial):
   - Portal de desenvolvedores RD (procure por "RD Station OAuth2 app").
2. Configure `RD_CLIENT_ID`, `RD_CLIENT_SECRET` e `RD_REDIRECT_URI`.
3. Faça o fluxo OAuth2 para obter `refresh_token`:
   - Autorize o app e troque o `code` por `access_token` + `refresh_token`.
   - O projeto usa `refresh_token` para obter novos `access_token` automaticamente em `rd_client.php` via `https://api.rd.services/auth/token`.
4. Endpoint de contatos: `https://api.rd.services/platform/contacts`.

Observação: Caso o RD exija e-mail como identificador principal, há duas estratégias:
  - (a) Criar e-mail sintético `phone@prospecta.local` (habilitado via `RD_ALLOW_SYNTHETIC_EMAIL=true`).
  - (b) Criar lead somente localmente e aguardar validação manual para preenchimento de e-mail antes de enviar ao RD (desabilite sintético).

## Exemplo de workflow no n8n
- Trigger: SerpAPI/Scraper => Node "Transform" (mapeia campos para `name`, `phone`, `website`, `address`, `city`, `state`, `country`, `lat`, `lng`, `rating`, `reviews_count`, `category`, `source`, `email` opcional).
- HTTP Request:
  - Método: `POST`
  - URL: `http://seu-servidor/webhook/lead`
  - Headers: `X-Api-Key: SUA_CHAVE_AQUI`, `Content-Type: application/json`
  - Body: JSON com os campos acima.

## Reprocessamento de falhas (cron)
- Script: `cron/reprocess.php`
- Seleciona leads com `rd_uuid IS NULL` e `rd_attempts < RD_MAX_ATTEMPTS`.
- Tenta reenviar ao RD. Registra em `rd_logs`.
- Configure um cron (Linux) ou agendador (Windows Task Scheduler):
  - Ex.: a cada 10 minutos: `php cron/reprocess.php`

## UI Administrativa
- Lista paginada com filtros e busca full-text (`q`).
- Seleção com checkboxes e envio em lote.
- Exportação CSV preservando filtros.
- Painel lateral com mapa (Leaflet) e pins; seleção destaca pin.
- Estatísticas rápidas: com WhatsApp, sem, rating médio, verificados.
- Modal de detalhe/edição do lead (salva via `POST /api/lead/{id}/update`).
- Página de configuração para atualizar `.env` com variáveis RD e API.

## Logs
- Tabela `rd_logs` armazena `lead_id`, ação, `status_code`, resposta.
- Arquivo `storage/logs/app.log` guarda eventos de execução.

## Notas técnicas
- CORS: habilitado em `includes/bootstrap.php` e responde preflight `OPTIONS` com 204.
- Deduplicação: consulta por `phone`, `website` e `email` antes de inserir; há índices únicos.
- Campos JSON: `raw_payload` usa tipo `JSON` (MySQL 5.7+). Se necessário, altere para `TEXT`.

## Desenvolvimento
- PHP 8+ recomendado (cURL para RD, JSON, etc.).
- Teste os endpoints com `curl` ou Postman.