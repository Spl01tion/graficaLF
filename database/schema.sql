-- ============================================================
--  Gráfica Lifei — Schema da base de dados (MySQL 8)
-- ------------------------------------------------------------
--  Convenções:
--   - InnoDB + utf8mb4 (suporte a acentos e emojis)
--   - Chaves estrangeiras com ON DELETE explícito
--   - Índices nas colunas usadas em filtros/junções (slug, FKs, status)
--   - Sem campos de pagamento online: o fluxo termina num pedido
--     de encomenda/orçamento (ver tabela `orders`)
--
--  Este ficheiro é idempotente: pode ser executado várias vezes
--  (CREATE TABLE IF NOT EXISTS). Para uma reinstalação limpa,
--  a página setup.php apaga as tabelas antes de recriar.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
--  UTILIZADORES E RECUPERAÇÃO DE SENHA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id_user      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(120)  NOT NULL,
    email        VARCHAR(150)  NOT NULL,
    telefone     VARCHAR(30)   NULL,
    password     VARCHAR(255)  NOT NULL,
    role         ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    image        VARCHAR(255)  NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id_reset     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(150) NOT NULL,
    token        VARCHAR(255) NOT NULL,
    expires_at   DATETIME     NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pr_email (email),
    KEY idx_pr_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  CATEGORIAS E MARCAS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id_category  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(120) NOT NULL,
    slug         VARCHAR(140) NOT NULL,
    descricao    VARCHAR(255) NULL,
    image        VARCHAR(255) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brands (
    id_brand     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(120) NOT NULL,
    slug         VARCHAR(140) NOT NULL,
    logo         VARCHAR(255) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_brands_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  PRODUTOS, IMAGENS E OPÇÕES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id_product   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_category  INT UNSIGNED NULL,
    id_brand     INT UNSIGNED NULL,
    nome         VARCHAR(180) NOT NULL,
    slug         VARCHAR(200) NOT NULL,
    sku          VARCHAR(60)  NULL,
    descricao_curta VARCHAR(255) NULL,
    descricao    TEXT NULL,
    preco        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    preco_promo  DECIMAL(10,2) NULL,
    stock        INT NOT NULL DEFAULT 0,
    destaque     TINYINT(1) NOT NULL DEFAULT 0,
    ativo        TINYINT(1) NOT NULL DEFAULT 1,
    vendas       INT NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (id_category),
    KEY idx_products_brand (id_brand),
    KEY idx_products_ativo (ativo),
    KEY idx_products_destaque (destaque),
    KEY idx_products_vendas (vendas),
    CONSTRAINT fk_products_category FOREIGN KEY (id_category)
        REFERENCES categories (id_category) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_products_brand FOREIGN KEY (id_brand)
        REFERENCES brands (id_brand) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id_image     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_product   INT UNSIGNED NOT NULL,
    image        VARCHAR(500) NOT NULL,          -- nome de ficheiro OU URL (Unsplash nos seeds)
    principal    TINYINT(1) NOT NULL DEFAULT 0,  -- imagem de capa
    ordem        INT NOT NULL DEFAULT 0,
    KEY idx_pimg_product (id_product),
    CONSTRAINT fk_pimg_product FOREIGN KEY (id_product)
        REFERENCES products (id_product) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opções/variações configuráveis pelo admin (ex.: Tamanho, Cor, Material,
-- Quantidade), cada valor com um ajuste de preço opcional.
CREATE TABLE IF NOT EXISTS product_options (
    id_option    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_product   INT UNSIGNED NOT NULL,
    grupo        VARCHAR(60)  NOT NULL,          -- "Tamanho", "Cor", "Material", "Quantidade"
    valor        VARCHAR(120) NOT NULL,          -- "M", "Azul", "Algodão", "500 un."
    ajuste_preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ordem        INT NOT NULL DEFAULT 0,
    KEY idx_popt_product (id_product),
    CONSTRAINT fk_popt_product FOREIGN KEY (id_product)
        REFERENCES products (id_product) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  CUPÕES (desconto informativo — sem gateway de pagamento)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    id_coupon    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(40) NOT NULL,
    tipo         ENUM('percentual', 'fixo') NOT NULL DEFAULT 'percentual',
    valor        DECIMAL(10,2) NOT NULL,          -- % (0-100) ou valor fixo em MZN
    minimo       DECIMAL(10,2) NOT NULL DEFAULT 0.00,  -- valor mínimo do pedido
    limite_uso   INT NULL,                         -- NULL = ilimitado
    usado        INT NOT NULL DEFAULT 0,
    inicia_em    DATE NULL,
    expira_em    DATE NULL,
    ativo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coupons_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  PEDIDOS (encomenda/orçamento — pagamento combinado à parte)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id_order         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referencia       VARCHAR(20) NOT NULL,            -- referência legível (ex.: GL-2026-0001)
    id_user          INT UNSIGNED NULL,               -- NULL para convidados (guest)
    cliente_nome     VARCHAR(150) NOT NULL,
    cliente_email    VARCHAR(150) NOT NULL,
    cliente_telefone VARCHAR(30)  NOT NULL,
    tipo_entrega     ENUM('entrega', 'levantamento') NOT NULL DEFAULT 'levantamento',
    endereco         VARCHAR(255) NULL,               -- obrigatório se tipo_entrega = entrega
    observacoes      TEXT NULL,
    id_coupon        INT UNSIGNED NULL,
    coupon_codigo    VARCHAR(40) NULL,
    subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    desconto         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status           ENUM('pendente','confirmado','em_producao','pronto','entregue','cancelado')
                        NOT NULL DEFAULT 'pendente',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_ref (referencia),
    KEY idx_orders_user (id_user),
    KEY idx_orders_status (status),
    KEY idx_orders_created (created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (id_user)
        REFERENCES users (id_user) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_orders_coupon FOREIGN KEY (id_coupon)
        REFERENCES coupons (id_coupon) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id_item      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_order     INT UNSIGNED NOT NULL,
    id_product   INT UNSIGNED NULL,               -- NULL se o produto for apagado
    produto_nome VARCHAR(180) NOT NULL,           -- "fotografia" do nome à data do pedido
    opcoes       VARCHAR(500) NULL,               -- opções escolhidas (ex.: "Tamanho: M; Cor: Azul")
    preco_unit   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantidade   INT NOT NULL DEFAULT 1,
    subtotal     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    KEY idx_oitem_order (id_order),
    CONSTRAINT fk_oitem_order FOREIGN KEY (id_order)
        REFERENCES orders (id_order) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_oitem_product FOREIGN KEY (id_product)
        REFERENCES products (id_product) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_history (
    id_history   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_order     INT UNSIGNED NOT NULL,
    status       ENUM('pendente','confirmado','em_producao','pronto','entregue','cancelado') NOT NULL,
    nota         VARCHAR(255) NULL,
    id_user      INT UNSIGNED NULL,               -- quem alterou (admin)
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_osh_order (id_order),
    CONSTRAINT fk_osh_order FOREIGN KEY (id_order)
        REFERENCES orders (id_order) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_osh_user FOREIGN KEY (id_user)
        REFERENCES users (id_user) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  WISHLIST E CARRINHO PERSISTENTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wishlists (
    id_wishlist  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user      INT UNSIGNED NOT NULL,
    id_product   INT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (id_user, id_product),
    KEY idx_wishlist_product (id_product),
    CONSTRAINT fk_wishlist_user FOREIGN KEY (id_user)
        REFERENCES users (id_user) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_wishlist_product FOREIGN KEY (id_product)
        REFERENCES products (id_product) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carts (
    id_cart      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user      INT UNSIGNED NULL,               -- carrinho de utilizador autenticado
    session_id   VARCHAR(128) NULL,               -- carrinho de convidado
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_carts_user (id_user),
    KEY idx_carts_session (session_id),
    CONSTRAINT fk_carts_user FOREIGN KEY (id_user)
        REFERENCES users (id_user) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
    id_cart_item INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cart      INT UNSIGNED NOT NULL,
    id_product   INT UNSIGNED NOT NULL,
    opcoes       VARCHAR(500) NULL,
    preco_unit   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantidade   INT NOT NULL DEFAULT 1,
    KEY idx_citem_cart (id_cart),
    KEY idx_citem_product (id_product),
    CONSTRAINT fk_citem_cart FOREIGN KEY (id_cart)
        REFERENCES carts (id_cart) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_citem_product FOREIGN KEY (id_product)
        REFERENCES products (id_product) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  CONFIGURAÇÕES DO SITE (chave-valor)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id_setting   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`        VARCHAR(100) NOT NULL,
    `value`      TEXT NULL,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  MENSAGENS DO FORMULÁRIO DE CONTACTO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id_message   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(120) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    telefone     VARCHAR(30)  NULL,
    mensagem     TEXT NOT NULL,
    lida         TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_msg_lida (lida),
    KEY idx_msg_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
