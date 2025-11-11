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

## Docroot do subdomínio
Para este projeto, o docroot informado é:

`domains/convert.r3mark.com.br/public_html`

Use o caminho relativo ao root do seu usuário FTP. Em muitos provedores (DirectAdmin, cPanel), o painel mostra com barra inicial (`/domains/...`), mas no FTP a forma correta costuma ser SEM a barra inicial: `domains/...`.

Se o deploy falhar com erro de caminho, altere removendo/adicionando a barra inicial conforme seu provedor e confirme o docroot no painel.

## Banco de dados
Crie o DB e importe `schema.sql`. Configure `.env` com as credenciais de produção.

## Deploy via extensão SFTP (VS Code / Trae)
Se preferir publicar diretamente do editor usando a extensão "SFTP/FTP sync":

- Instale a extensão (Natizyskunks — SFTP/FTP sync).
- O arquivo de configuração já está em `.vscode/sftp.json`.
  - `host`: `painel.r3mark.com.br`
  - `protocol`: `ftp` (mude para `ftps` se desejar TLS explícito)
  - `port`: `21`
  - `username`: `rmarkco1` (a senha será solicitada ao conectar)
  - `remotePath`: `domains/convert.r3mark.com.br/public_html` (sem barra inicial)
- Comandos úteis (Command Palette):
  - "SFTP: Sync Local -> Remote" (envia todo o projeto; respeita exclusões)
  - "SFTP: Upload Active File" (envia apenas o arquivo aberto)
  - "SFTP: List/Download" (útil para conferir o que há no servidor)
- Após a sincronização, valide:
  - Homepage: `https://convert.r3mark.com.br/` deve exibir a UI do projeto.
  - API: `https://convert.r3mark.com.br/api/stats` deve retornar 200 com JSON.

Notas:
- `.vscode/` está ignorado no `.gitignore` para não versionar credenciais locais.
- Se ocorrer erro de caminho, teste a variante com barra inicial: `/domains/convert.r3mark.com.br/public_html`.
- Se houver erro de TLS, mude `protocol` para `ftps` ou volte a `ftp` conforme o suporte do provedor.