<?php

/**
 * ============================================================
 *  seeds.php — Dados de exemplo (produtos reais de gráfica)
 * ------------------------------------------------------------
 *  Executado pela página setup.php. Usa as funções do núcleo
 *  (query/inserir) e password_hash() para o utilizador admin.
 *
 *  É idempotente: se já existirem dados, não duplica.
 *
 *  URLs das imagens: Unsplash (todas verificadas a devolver 200).
 * ============================================================
 */

declare(strict_types=1);

/**
 * Popula a base de dados. Devolve um relatório do que foi inserido.
 *
 * @return array<string,int>
 */
function executar_seeds(): array
{
    $rel = [];

    $img = static fn (string $id): string => 'https://images.unsplash.com/photo-' . $id . '?w=800&q=80&auto=format&fit=crop';

    // ------------------------------------------------------------
    //  1. CONFIGURAÇÕES DO SITE (settings)
    // ------------------------------------------------------------
    $settings = [
        'site_name'              => 'Gráfica Lifei',
        'site_email'             => 'geral@graficalifei.co.mz',
        'site_phone'             => '+258 84 000 0000',
        'site_whatsapp'          => '258840000000',
        'site_address'           => 'Av. Julius Nyerere, nº 123, Maputo, Moçambique',
        'orders_email'           => 'pedidos@graficalifei.co.mz',
        'free_shipping_min'      => '5000',
        'promo_text'             => 'Entrega grátis em Maputo para pedidos acima de 5.000 MZN',
        'facebook_url'           => 'https://facebook.com/graficalifei',
        'instagram_url'          => 'https://instagram.com/graficalifei',
        'whatsapp_url'           => 'https://wa.me/258840000000',
        'maps_embed'             => 'https://www.google.com/maps?q=Maputo&output=embed',
    ];
    foreach ($settings as $k => $v) {
        if (! query_row('SELECT id_setting FROM settings WHERE `key` = :k', ['k' => $k])) {
            inserir('settings', ['key' => $k, 'value' => $v]);
            $rel['settings'] = ($rel['settings'] ?? 0) + 1;
        }
    }

    // ------------------------------------------------------------
    //  2. MARCAS (brands)
    // ------------------------------------------------------------
    $brands = [
        ['nome' => 'Lifei Premium',   'slug' => 'lifei-premium'],
        ['nome' => 'Lifei Essential', 'slug' => 'lifei-essential'],
        ['nome' => 'EcoPrint',        'slug' => 'ecoprint'],
    ];
    $idBrand = [];
    foreach ($brands as $b) {
        $existe = query_row('SELECT id_brand FROM brands WHERE slug = :s', ['s' => $b['slug']]);
        $idBrand[$b['slug']] = $existe ? (int) $existe['id_brand'] : inserir('brands', $b);
        if (! $existe) {
            $rel['brands'] = ($rel['brands'] ?? 0) + 1;
        }
    }

    // ------------------------------------------------------------
    //  3. CATEGORIAS (categories)
    // ------------------------------------------------------------
    $categorias = [
        ['nome' => 'Cartões de Visita',    'slug' => 'cartoes-de-visita',    'descricao' => 'Cartões profissionais em vários acabamentos.',      'image' => $img('1611532736579-6b16e2b50449')],
        ['nome' => 'Banners & Roll-ups',   'slug' => 'banners-roll-ups',     'descricao' => 'Banners em lona e roll-ups para eventos e lojas.',  'image' => $img('1620799140408-edc6dcb6d633')],
        ['nome' => 'T-shirts & Vestuário', 'slug' => 't-shirts-vestuario',   'descricao' => 'Personalização de t-shirts, bonés e uniformes.',    'image' => $img('1523381210434-271e8be1f52b')],
        ['nome' => 'Stickers & Etiquetas', 'slug' => 'stickers-etiquetas',   'descricao' => 'Autocolantes e etiquetas em vinil e papel.',        'image' => $img('1560343090-f0409e92791a')],
        ['nome' => 'Livros & Catálogos',   'slug' => 'livros-catalogos',     'descricao' => 'Impressão e encadernação de livros e catálogos.',   'image' => $img('1524234107056-1c1f48f64ab8')],
        ['nome' => 'Flyers & Cartazes',    'slug' => 'flyers-cartazes',      'descricao' => 'Flyers, folhetos e cartazes de alto impacto.',      'image' => $img('1493655161922-ef98929de9d8')],
    ];
    $idCat = [];
    foreach ($categorias as $c) {
        $existe = query_row('SELECT id_category FROM categories WHERE slug = :s', ['s' => $c['slug']]);
        $idCat[$c['slug']] = $existe ? (int) $existe['id_category'] : inserir('categories', $c);
        if (! $existe) {
            $rel['categories'] = ($rel['categories'] ?? 0) + 1;
        }
    }

    // ------------------------------------------------------------
    //  4. PRODUTOS (+ imagens + opções)
    // ------------------------------------------------------------
    // Cada produto: dados + imagem + opções configuráveis.
    $produtos = [
        [
            'cat' => 'cartoes-de-visita', 'brand' => 'lifei-premium',
            'nome' => 'Cartões de Visita Premium 350g', 'preco' => 850, 'promo' => null,
            'destaque' => 1, 'vendas' => 320, 'stock' => 500,
            'curta' => 'Papel couché 350g com acabamento à sua escolha.',
            'desc'  => 'Cartões de visita premium impressos em papel couché de 350g/m². Cores vivas, corte preciso e acabamento profissional que causa boa primeira impressão. Ideais para empresários e profissionais liberais.',
            'img'   => '1611532736579-6b16e2b50449',
            'opcoes' => [
                ['grupo' => 'Quantidade', 'valor' => '100 un.',  'ajuste' => 0],
                ['grupo' => 'Quantidade', 'valor' => '250 un.',  'ajuste' => 350],
                ['grupo' => 'Quantidade', 'valor' => '500 un.',  'ajuste' => 750],
                ['grupo' => 'Quantidade', 'valor' => '1000 un.', 'ajuste' => 1300],
                ['grupo' => 'Acabamento', 'valor' => 'Mate',      'ajuste' => 0],
                ['grupo' => 'Acabamento', 'valor' => 'Brilhante', 'ajuste' => 0],
                ['grupo' => 'Acabamento', 'valor' => 'Verniz UV', 'ajuste' => 200],
            ],
        ],
        [
            'cat' => 'cartoes-de-visita', 'brand' => 'lifei-essential',
            'nome' => 'Cartões de Visita Clássicos 300g', 'preco' => 550, 'promo' => 450,
            'destaque' => 0, 'vendas' => 210, 'stock' => 500,
            'curta' => 'Solução económica em papel 300g.',
            'desc'  => 'Cartões de visita clássicos em papel de 300g/m², com óptima relação qualidade-preço. Perfeitos para quem procura um cartão simples e elegante.',
            'img'   => '1583744946564-b52ac1c389c8',
            'opcoes' => [
                ['grupo' => 'Quantidade', 'valor' => '100 un.', 'ajuste' => 0],
                ['grupo' => 'Quantidade', 'valor' => '250 un.', 'ajuste' => 250],
                ['grupo' => 'Quantidade', 'valor' => '500 un.', 'ajuste' => 500],
            ],
        ],
        [
            'cat' => 'banners-roll-ups', 'brand' => 'lifei-premium',
            'nome' => 'Roll-up Banner 85x200cm', 'preco' => 3500, 'promo' => null,
            'destaque' => 1, 'vendas' => 145, 'stock' => 80,
            'curta' => 'Roll-up com estrutura de alumínio e bolsa de transporte.',
            'desc'  => 'Roll-up banner de 85x200cm com impressão em alta resolução, estrutura de alumínio reutilizável e bolsa de transporte incluída. Montagem em segundos — ideal para feiras, conferências e pontos de venda.',
            'img'   => '1620799140408-edc6dcb6d633',
            'opcoes' => [
                ['grupo' => 'Material', 'valor' => 'Lona Premium', 'ajuste' => 0],
                ['grupo' => 'Material', 'valor' => 'Vinil',        'ajuste' => 300],
            ],
        ],
        [
            'cat' => 'banners-roll-ups', 'brand' => 'ecoprint',
            'nome' => 'Banner em Lona 2x1m', 'preco' => 1800, 'promo' => null,
            'destaque' => 0, 'vendas' => 98, 'stock' => 120,
            'curta' => 'Lona resistente com ilhós para exterior.',
            'desc'  => 'Banner em lona de 2x1 metros, resistente a intempéries, com ilhós metálicos reforçados para fácil fixação. Impressão vibrante para uso interior e exterior.',
            'img'   => '1607435097405-db48f377bff6',
            'opcoes' => [
                ['grupo' => 'Tamanho', 'valor' => '2x1 m', 'ajuste' => 0],
                ['grupo' => 'Tamanho', 'valor' => '3x1 m', 'ajuste' => 700],
                ['grupo' => 'Tamanho', 'valor' => '4x2 m', 'ajuste' => 2200],
            ],
        ],
        [
            'cat' => 't-shirts-vestuario', 'brand' => 'lifei-premium',
            'nome' => 'T-shirt Personalizada DTF', 'preco' => 650, 'promo' => null,
            'destaque' => 1, 'vendas' => 410, 'stock' => 300,
            'curta' => 'T-shirt de algodão com estampa DTF durável.',
            'desc'  => 'T-shirt 100% algodão com impressão DTF (Direct-to-Film) de alta durabilidade e cores intensas que resistem a muitas lavagens. Personalize com o seu logótipo, arte ou frase.',
            'img'   => '1523381210434-271e8be1f52b',
            'opcoes' => [
                ['grupo' => 'Tamanho', 'valor' => 'S',   'ajuste' => 0],
                ['grupo' => 'Tamanho', 'valor' => 'M',   'ajuste' => 0],
                ['grupo' => 'Tamanho', 'valor' => 'L',   'ajuste' => 50],
                ['grupo' => 'Tamanho', 'valor' => 'XL',  'ajuste' => 100],
                ['grupo' => 'Cor', 'valor' => 'Branco', 'ajuste' => 0],
                ['grupo' => 'Cor', 'valor' => 'Preto',  'ajuste' => 0],
                ['grupo' => 'Cor', 'valor' => 'Azul',   'ajuste' => 0],
            ],
        ],
        [
            'cat' => 't-shirts-vestuario', 'brand' => 'lifei-essential',
            'nome' => 'Boné Bordado', 'preco' => 750, 'promo' => null,
            'destaque' => 0, 'vendas' => 175, 'stock' => 200,
            'curta' => 'Boné com bordado personalizado de alta qualidade.',
            'desc'  => 'Boné de qualidade com bordado computorizado do seu logótipo. Fecho ajustável, tecido durável e acabamento premium. Óptimo para brindes corporativos e equipas.',
            'img'   => '1588850561407-ed78c282e89b',
            'opcoes' => [
                ['grupo' => 'Cor', 'valor' => 'Preto', 'ajuste' => 0],
                ['grupo' => 'Cor', 'valor' => 'Bege',  'ajuste' => 0],
                ['grupo' => 'Cor', 'valor' => 'Azul Marinho', 'ajuste' => 0],
            ],
        ],
        [
            'cat' => 'stickers-etiquetas', 'brand' => 'ecoprint',
            'nome' => 'Stickers em Vinil (100 un.)', 'preco' => 400, 'promo' => null,
            'destaque' => 1, 'vendas' => 530, 'stock' => 1000,
            'curta' => 'Autocolantes em vinil resistente à água.',
            'desc'  => 'Pacote de 100 stickers em vinil recortado, resistentes à água e aos raios UV. Ideais para embalagens, portáteis, garrafas e brindes. Formato personalizado à sua escolha.',
            'img'   => '1560343090-f0409e92791a',
            'opcoes' => [
                ['grupo' => 'Formato', 'valor' => 'Redondo',  'ajuste' => 0],
                ['grupo' => 'Formato', 'valor' => 'Quadrado', 'ajuste' => 0],
                ['grupo' => 'Formato', 'valor' => 'Recorte personalizado', 'ajuste' => 150],
            ],
        ],
        [
            'cat' => 'stickers-etiquetas', 'brand' => 'lifei-essential',
            'nome' => 'Etiquetas Adesivas em Rolo', 'preco' => 950, 'promo' => null,
            'destaque' => 0, 'vendas' => 88, 'stock' => 400,
            'curta' => 'Etiquetas em rolo para produtos e logística.',
            'desc'  => 'Etiquetas adesivas fornecidas em rolo, perfeitas para rotulagem de produtos, códigos de barras e envios. Adesivo forte e impressão nítida.',
            'img'   => '1598032895397-b9472444bf93',
            'opcoes' => [],
        ],
        [
            'cat' => 'livros-catalogos', 'brand' => 'lifei-premium',
            'nome' => 'Catálogo A4 Agrafado (20 pág.)', 'preco' => 1200, 'promo' => null,
            'destaque' => 0, 'vendas' => 64, 'stock' => 150,
            'curta' => 'Catálogo A4 a cores, agrafado a cavalo.',
            'desc'  => 'Catálogo em formato A4 com 20 páginas a cores, agrafado a cavalo, em papel couché brilhante. Ideal para apresentar produtos e serviços da sua empresa.',
            'img'   => '1524234107056-1c1f48f64ab8',
            'opcoes' => [
                ['grupo' => 'Páginas', 'valor' => '20 páginas', 'ajuste' => 0],
                ['grupo' => 'Páginas', 'valor' => '32 páginas', 'ajuste' => 600],
                ['grupo' => 'Páginas', 'valor' => '48 páginas', 'ajuste' => 1400],
            ],
        ],
        [
            'cat' => 'livros-catalogos', 'brand' => 'lifei-premium',
            'nome' => 'Livro Capa Dura Personalizado', 'preco' => 2800, 'promo' => null,
            'destaque' => 1, 'vendas' => 42, 'stock' => 60,
            'curta' => 'Encadernação de capa dura profissional.',
            'desc'  => 'Impressão e encadernação de livros com capa dura, ideal para memórias, portfólios, teses e edições especiais. Acabamento de luxo com costura reforçada.',
            'img'   => '1544716278-ca5e3f4abd8c',
            'opcoes' => [],
        ],
        [
            'cat' => 'flyers-cartazes', 'brand' => 'ecoprint',
            'nome' => 'Flyers A5 Coloridos (500 un.)', 'preco' => 2200, 'promo' => 1900,
            'destaque' => 1, 'vendas' => 267, 'stock' => 800,
            'curta' => '500 flyers A5 a cores, frente e verso.',
            'desc'  => 'Pacote de 500 flyers em formato A5, impressos a cores frente e verso em papel couché 150g. Perfeitos para promoções, eventos e campanhas de marketing.',
            'img'   => '1493655161922-ef98929de9d8',
            'opcoes' => [
                ['grupo' => 'Impressão', 'valor' => 'Só frente',      'ajuste' => 0],
                ['grupo' => 'Impressão', 'valor' => 'Frente e verso', 'ajuste' => 400],
            ],
        ],
        [
            'cat' => 'flyers-cartazes', 'brand' => 'lifei-essential',
            'nome' => 'Cartaz A2 Alta Resolução', 'preco' => 500, 'promo' => null,
            'destaque' => 0, 'vendas' => 133, 'stock' => 300,
            'curta' => 'Cartaz A2 vibrante para montras e eventos.',
            'desc'  => 'Cartaz em formato A2 impresso em alta resolução, com cores vivas e papel de gramagem elevada. Chame a atenção em montras, paredes e eventos.',
            'img'   => '1503694978374-8a2fa686963a',
            'opcoes' => [
                ['grupo' => 'Tamanho', 'valor' => 'A2', 'ajuste' => 0],
                ['grupo' => 'Tamanho', 'valor' => 'A1', 'ajuste' => 350],
                ['grupo' => 'Tamanho', 'valor' => 'A0', 'ajuste' => 800],
            ],
        ],
    ];

    foreach ($produtos as $p) {
        $slug = str_to_url($p['nome']);
        if (query_row('SELECT id_product FROM products WHERE slug = :s', ['s' => $slug])) {
            continue; // já existe
        }

        $idProduto = inserir('products', [
            'id_category'     => $idCat[$p['cat']] ?? null,
            'id_brand'        => $idBrand[$p['brand']] ?? null,
            'nome'            => $p['nome'],
            'slug'            => $slug,
            'sku'             => 'GL-' . strtoupper(substr(md5($slug), 0, 6)),
            'descricao_curta' => $p['curta'],
            'descricao'       => $p['desc'],
            'preco'           => $p['preco'],
            'preco_promo'     => $p['promo'],
            'stock'           => $p['stock'],
            'destaque'        => $p['destaque'],
            'ativo'           => 1,
            'vendas'          => $p['vendas'],
        ]);
        $rel['products'] = ($rel['products'] ?? 0) + 1;

        // Imagem principal
        inserir('product_images', [
            'id_product' => $idProduto,
            'image'      => $img($p['img']),
            'principal'  => 1,
            'ordem'      => 0,
        ]);
        $rel['product_images'] = ($rel['product_images'] ?? 0) + 1;

        // Opções
        foreach ($p['opcoes'] as $i => $o) {
            inserir('product_options', [
                'id_product'   => $idProduto,
                'grupo'        => $o['grupo'],
                'valor'        => $o['valor'],
                'ajuste_preco' => $o['ajuste'],
                'ordem'        => $i,
            ]);
            $rel['product_options'] = ($rel['product_options'] ?? 0) + 1;
        }
    }

    // ------------------------------------------------------------
    //  5. CUPÕES
    // ------------------------------------------------------------
    $cupoes = [
        ['codigo' => 'BEMVINDO10', 'tipo' => 'percentual', 'valor' => 10, 'minimo' => 1000, 'limite_uso' => 100, 'expira_em' => date('Y-m-d', strtotime('+3 months')), 'ativo' => 1],
        ['codigo' => 'LIFEI500',   'tipo' => 'fixo',       'valor' => 500, 'minimo' => 3000, 'limite_uso' => 50,  'expira_em' => date('Y-m-d', strtotime('+1 month')),  'ativo' => 1],
    ];
    foreach ($cupoes as $cp) {
        if (! query_row('SELECT id_coupon FROM coupons WHERE codigo = :c', ['c' => $cp['codigo']])) {
            inserir('coupons', $cp);
            $rel['coupons'] = ($rel['coupons'] ?? 0) + 1;
        }
    }

    // ------------------------------------------------------------
    //  6. UTILIZADORES (admin + cliente de exemplo)
    // ------------------------------------------------------------
    $utilizadores = [
        ['nome' => 'Administrador Lifei', 'email' => 'admin@graficalifei.co.mz', 'telefone' => '+258 84 000 0000', 'pass' => 'admin123',   'role' => 'admin'],
        ['nome' => 'João Cliente',        'email' => 'cliente@exemplo.co.mz',    'telefone' => '+258 82 111 2222', 'pass' => 'cliente123', 'role' => 'customer'],
    ];
    foreach ($utilizadores as $u) {
        if (! query_row('SELECT id_user FROM users WHERE email = :e', ['e' => $u['email']])) {
            inserir('users', [
                'nome'     => $u['nome'],
                'email'    => $u['email'],
                'telefone' => $u['telefone'],
                'password' => password_hash($u['pass'], PASSWORD_DEFAULT),
                'role'     => $u['role'],
            ]);
            $rel['users'] = ($rel['users'] ?? 0) + 1;
        }
    }

    return $rel;
}
