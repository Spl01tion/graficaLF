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
    //  3. CATEGORIAS E PRODUTOS (database/catalogo.php)
    // ------------------------------------------------------------
    // O catálogo real do cliente vive em database/catalogo.php e é
    // inserido por database/seed_catalogo.php. Aqui só corre se a loja
    // ainda estiver vazia — este seed nunca apaga um catálogo já
    // existente. Para substituir o catálogo use /setup?catalogo=1.
    if (! query_row('SELECT id_product FROM products LIMIT 1')) {
        require_once BASE_PATH . '/database/seed_catalogo.php';
        $catalogo = executar_catalogo();

        $rel['categories']      = (int) $catalogo['categorias criadas'];
        $rel['products']        = (int) $catalogo['produtos criados'];
        $rel['product_images']  = (int) $catalogo['imagens criadas'];
        $rel['product_options'] = (int) $catalogo['opções criadas'];
    }

    // ------------------------------------------------------------
    //  4. CUPÕES
    // ------------------------------------------------------------
    $cupoes = [
        // LIFEI10 é o código anunciado na faixa de campanha da página inicial.
        ['codigo' => 'LIFEI10',    'tipo' => 'percentual', 'valor' => 10, 'minimo' => 0,    'limite_uso' => null, 'expira_em' => null, 'ativo' => 1],
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
    //  5. UTILIZADORES (admin + cliente de exemplo)
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
