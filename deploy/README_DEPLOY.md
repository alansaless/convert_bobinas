# Deploy via GitHub Actions

Este repositório inclui um workflow de deploy por FTP/FTPS (ideal para cPanel). Para ativá-lo, configure os seguintes Secrets no repositório do GitHub:

- `FTP_SERVER`: host do FTP (ex.: `ftp.seu-dominio.com` ou o host do provedor).
- `FTP_USERNAME`: usuário do FTP (cPanel geralmente usa o mesmo usuário com `@dominio`).
- `FTP_PASSWORD`: senha do FTP.
- `FTP_PORT`: porta (comum: `21` para FTP, `990` para FTPS). Se FTPS não funcionar, troque `protocol: ftps` para `ftp` no workflow.
- `SERVER_DIR`: diretório destino no servidor (ex.: `public_html/captacao` ou `public_html`).

Opcionalmente, se preferir SSH ao invés de FTP:
- `SSH_PRIVATE_KEY`: chave privada do deploy (Ed25519),
- `SSH_USER`: usuário SSH,
- `SSH_HOST`: host SSH,
- Ajuste o job `deploy-ssh` no workflow (descomente e comente o job FTP).

## Arquivos sensíveis
`/.env` não é enviado. Crie `.env` no servidor com valores de produção (`DB_*`, `API_KEY`, `RD_*`).

## cPanel
Para cPanel, o `SERVER_DIR` costuma ser `public_html/SEU_SUBDIRETORIO`. Crie a pasta destino antes ou deixe o deploy criar.

## Banco de dados
Crie o DB e importe `schema.sql`. Configure `.env` com as credenciais de produção.