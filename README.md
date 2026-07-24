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
