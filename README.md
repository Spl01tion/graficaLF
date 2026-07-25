# Gráfica Lifei — Website & Loja Online

Website institucional e loja online da **Gráfica Lifei** (gráfica e serviço de
impressão em Maputo, Moçambique). Desenvolvido em **PHP puro** com arquitetura
MVC própria (ao estilo do projecto MaxaPark), **Bootstrap 5** e **MySQL 8**.

> O fluxo de compra termina num **pedido de encomenda/orçamento por email** —
> não há pagamento online. O pagamento e a entrega são combinados à parte.

---

## Stack

- **PHP 8.2+** — arquitectura MVC própria, sem framework pesado
- **Bootstrap 5** (via CDN) + JavaScript vanilla
- **MySQL 8** — PDO com *prepared statements*
- **Composer**: `phpmailer/phpmailer` (emails), `vlucas/phpdotenv` (.env)

## Instalação (XAMPP)

1. Coloque o projecto em `c:\xampp\htdocs\grafica_lifei`.
2. Instale as dependências:
   ```bash
   composer install
   ```
3. Copie o ambiente e ajuste os valores:
   ```bash
   cp .env.example .env
   ```
   Confirme `APP_URL`, as credenciais `DB_*` e (opcional) o `MAIL_*`.
4. Aponte o browser para `.../grafica_lifei/public/setup` e clique em
   **Instalar** — cria as tabelas e insere os dados de exemplo.
   *(Disponível apenas com `APP_ENV=local`.)*
5. Abra `.../grafica_lifei/public/`.

### Credenciais iniciais (altere depois)

| Perfil  | Email                        | Senha        |
|---------|------------------------------|--------------|
| Admin   | `admin@graficalifei.co.mz`   | `admin123`   |
| Cliente | `cliente@exemplo.co.mz`      | `cliente123` |

O painel de administração fica em `/admin`.

## Deploy em produção (InfinityFree)

Contas gratuitas da InfinityFree só dão acesso, via FTP/Gestor de Ficheiros, à
pasta `htdocs/` (não é possível colocar pastas ao lado dela). Por isso, ao
contrário do XAMPP local (onde `public/` é uma subpasta e `app/`, `vendor/`,
etc. ficam fora do webroot), em produção **tudo** é enviado para dentro de
`htdocs/`, mas com o *conteúdo* de `public/` "achatado" para a raiz:

```
htdocs/
  index.php          <- vinha de public/index.php
  .htaccess          <- vinha de public/.htaccess
  assets/            <- vinha de public/assets/
  uploads/           <- vinha de public/uploads/
  app/                (pasta inteira, como está no repositório)
  vendor/             (pasta inteira — correr "composer install" antes)
  storage/            (pasta inteira)
  database/           (pasta inteira)
  .env                <- conteúdo de .env.production (renomeado)
  composer.json / composer.lock
```

O código já trata este layout automaticamente: `public/index.php` detecta se
`app/` está uma pasta acima (dev) ou ao lado (produção), e `UPLOADS_PATH`
(em `app/core/config.php`) faz o mesmo. As pastas `app/`, `vendor/`,
`storage/` e `database/` têm cada uma um `.htaccess` a negar acesso HTTP
directo, e `.env`/`composer.*`/`README.md` são bloqueados pelo `.htaccess`
da raiz — mas continuam fisicamente dentro do webroot, por isso **não
enviar nada que não conste da lista acima** (não enviar `.git/`, `README.md`
não é necessário, etc.).

### Passos (primeiro deploy, manual)

1. `composer install --no-dev --optimize-autoloader` localmente (gera o
   `vendor/` de produção, sem as dependências de desenvolvimento).
2. Preencha o `.env.production` (`APP_URL` com o domínio real; `MAIL_*` —
   a InfinityFree bloqueia SMTP directo, use um serviço externo como Gmail,
   Brevo ou Mailgun) e envie-o como `.env` para a raiz do `htdocs`.
   **Este ficheiro nunca é enviado pelo Git/CI — só existe no servidor.**
3. Envie os ficheiros/pastas listados acima via FTP (ou o Gestor de
   Ficheiros web da InfinityFree).
4. Crie a base de dados (já feito — `if0_41931075_graficalf_db`) e importe
   `database/schema.sql` via phpMyAdmin (Painel da InfinityFree → MySQL
   Databases → phpMyAdmin).
5. Para os dados de exemplo (categorias, produtos, utilizador admin): a
   forma mais simples é mudar temporariamente `APP_ENV=local` no `.env` de
   produção, visitar `https://<dominio>/setup` e clicar em **Instalar**
   (executa `database/seeds.php`), e de seguida repor `APP_ENV=production`.
   Ou, alternativamente, importe os `INSERT`s manualmente via phpMyAdmin.
6. **Mude a senha do admin** (a semente cria `admin@graficalifei.co.mz` /
   `admin123`) assim que conseguir entrar no painel.
7. Confirme no `.env`: `APP_ENV=production`, `APP_DEBUG=false`.

### Deploys seguintes (automático via GitHub Actions)

O workflow [`.github/workflows/deploy-infinityfree.yml`](.github/workflows/deploy-infinityfree.yml)
corre a cada push para `main` (ou manualmente em *Actions → Run workflow*):
instala as dependências (`composer install --no-dev`), monta o layout
achatado descrito acima em `dist/` e publica-o via FTP na InfinityFree.

Requer estes secrets em **Settings → Secrets and variables → Actions**:

| Secret          | Valor                                              |
|-----------------|-----------------------------------------------------|
| `FTP_SERVER`    | Servidor FTP da InfinityFree (ex.: `ftpupload.net`) |
| `FTP_USERNAME`  | Utilizador FTP                                      |
| `FTP_PASSWORD`  | Password FTP                                        |

O workflow **nunca** toca em `.env`, `uploads/` nem `storage/logs/` no
servidor (excluídos explicitamente) — o `.env` de produção só é criado à
mão, uma vez, no passo 2 acima. Se a tua conta FTP já entra directamente
dentro de `htdocs/` (em vez de mostrar `htdocs/` como subpasta), ajusta
`server-dir: ./htdocs/` para `server-dir: ./` no workflow.

## Estrutura

```
app/
  core/         init, config, conexao (PDO), functions, mail, loja
  pages/        páginas (roteamento por ficheiro) + admin/ + partials/
config/         não usado (config vive em core/config.php + .env)
database/       schema.sql + seeds.php
public/         index.php (front controller), .htaccess, assets/, uploads/
storage/logs/   logs da app + emails simulados (dev)
```

O `public/index.php` é o **único ponto de entrada**: o `.htaccess` reescreve
todos os pedidos para lá e o URL (`?url=...`) escolhe a página em `app/pages/`.

## Segurança

- **SQL injection** — 100% *prepared statements* (PDO, `EMULATE_PREPARES` off)
- **XSS** — todo o output passa por `e()` (`htmlspecialchars`)
- **CSRF** — token em todos os formulários (`csrf_field()` / `csrf_verificar()`)
- **Sessões** — cookies `httponly`/`samesite`/`secure` + regeneração de ID
- **RBAC** — `controlo_login()` / `controlo_admin()` protegem as rotas
- **Senhas** — `password_hash()` / `password_verify()` (bcrypt)
- **Uploads** — validação de MIME real, tamanho e renomeação; execução de
  PHP desligada em `/uploads` (`.htaccess`)
- **Rate limiting** — login, contacto e pedidos
- **`.env`** — fora do webroot e nunca versionado

## Email

O `app/core/mail.php` usa PHPMailer com as credenciais SMTP do `.env`.
**Sem SMTP configurado** (desenvolvimento), os emails são guardados como
ficheiros HTML em `storage/logs/emails/` — permitindo testar todo o fluxo
(recuperação de senha, confirmação de pedidos) sem servidor de email.

## SEO

- Meta tags + Open Graph + dados estruturados (JSON-LD `LocalBusiness`)
- `/sitemap.xml` e `/robots.txt` gerados dinamicamente
- URLs amigáveis

## Módulos

0. Fundação (MVC, .env, PDO, layout base)
1. Base de dados (schema + seeds)
2. Autenticação & permissões (login, registo, recuperação, RBAC)
3. Página inicial
4. Páginas institucionais (serviços, sobre, contacto)
5. Shop (listagem, filtros, página de produto)
6. Carrinho, wishlist & fazer pedido (por email)
7. Painel de administração (CRUD completo + KPIs)
8. Emails, SEO e finalização
