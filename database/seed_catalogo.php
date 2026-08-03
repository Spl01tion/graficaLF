<?php

/**
 * ============================================================
 *  seed_catalogo.php — Substitui o catálogo do site
 * ------------------------------------------------------------
 *  APAGA todas as categorias e produtos existentes e insere o
 *  catálogo definido em database/catalogo.php.
 *
 *  Executa-se pelo instalador:  /setup?catalogo=1
 *
 *  O que é derivado (a lista do cliente só traz nomes):
 *    - slug      : str_to_url() do nome, com sufixo se repetir
 *    - preço     : faixa por categoria + agravamento dos
 *                  acabamentos especiais, sempre determinístico
 *                  (o mesmo nome dá sempre o mesmo preço)
 *    - descrições: modelo por categoria com o nome do produto
 *    - imagem    : duas fotografias do conjunto da categoria
 *
 *  Os preços são de arranque — devem ser revistos no painel de
 *  administração antes de o site ir para o ar.
 * ============================================================
 */

declare(strict_types=1);

/**
 * Apaga o catálogo actual e insere o novo.
 *
 * @return array<string,int|string>  relatório da operação
 */
function executar_catalogo(): array
{
    require_once BASE_PATH . '/database/catalogo.php';

    $categorias = catalogo_categorias();
    $prioridade = catalogo_prioridade();

    // ------------------------------------------------------------
    //  1. Produtos repetidos em várias categorias
    // ------------------------------------------------------------
    // A tabela `products` só admite uma categoria por produto. Cada
    // nome fica na categoria mais específica em que aparece — a que
    // vier primeiro na lista de prioridade.
    $destino = [];
    foreach ($prioridade as $slugCat) {
        foreach ($categorias[$slugCat]['produtos'] as $nome) {
            $destino[$nome] ??= $slugCat;
        }
    }

    // ------------------------------------------------------------
    //  2. Apagar o catálogo actual
    // ------------------------------------------------------------
    // Sem desligar as chaves estrangeiras: apagar os produtos leva
    // consigo imagens, opções, carrinhos e favoritos (ON DELETE
    // CASCADE) e põe order_items.id_product a NULL, mantendo o
    // histórico de pedidos com o nome do produto à data.
    $apagados = [
        'produtos'   => execute('DELETE FROM products'),
        'categorias' => execute('DELETE FROM categories'),
    ];
    foreach (['products', 'categories', 'product_images'] as $t) {
        execute("ALTER TABLE `{$t}` AUTO_INCREMENT = 1");
    }

    // Marcas: são reaproveitadas (não fazem parte do pedido de limpeza).
    $marcas = [];
    foreach (query('SELECT id_brand, slug FROM brands') ?: [] as $b) {
        $marcas[$b['slug']] = (int) $b['id_brand'];
    }

    // ------------------------------------------------------------
    //  3. Inserir categorias e produtos
    // ------------------------------------------------------------
    $foto = static fn (string $id): string =>
        'https://images.unsplash.com/photo-' . $id . '?w=800&q=80&auto=format&fit=crop';

    $slugsUsados = [];
    $nCategorias = 0;
    $nProdutos   = 0;
    $nImagens    = 0;
    $nOpcoes     = 0;
    $porCategoria = [];

    foreach ($categorias as $slugCat => $cat) {
        $idCategoria = inserir('categories', [
            'nome'      => $cat['nome'],
            'slug'      => $slugCat,
            'descricao' => $cat['descricao'],
            'image'     => $foto($cat['fotos'][0]),
        ]);
        $nCategorias++;
        $porCategoria[$cat['nome']] = 0;

        $i = 0;
        foreach ($cat['produtos'] as $nome) {
            // Fica noutra categoria (produto repetido na lista original).
            if ($destino[$nome] !== $slugCat) {
                continue;
            }

            // -- Slug único (a coluna tem índice UNIQUE) ---------
            $base = str_to_url($nome);
            $slug = $base;
            $n    = 1;
            while (isset($slugsUsados[$slug])) {
                $slug = $base . '-' . (++$n);
            }
            $slugsUsados[$slug] = true;

            // -- Valores determinísticos a partir do nome ---------
            $semente = crc32($slug);
            $preco   = catalogo_preco($slugCat, $nome, $semente);

            $idProduto = inserir('products', [
                'id_category'     => $idCategoria,
                'id_brand'        => catalogo_marca($nome, $semente, $marcas),
                'nome'            => $nome,
                'slug'            => $slug,
                'sku'             => 'LF-' . str_pad((string) ($nProdutos + 1), 4, '0', STR_PAD_LEFT),
                'descricao_curta' => catalogo_descricao_curta($slugCat, $nome),
                'descricao'       => catalogo_descricao($slugCat, $nome),
                'preco'           => $preco,
                // Cerca de 1 em cada 7 produtos entra em promoção (-15%).
                'preco_promo'     => $semente % 7 === 0 ? round($preco * 0.85 / 50) * 50 : null,
                'stock'           => 50 + ($semente % 96) * 10,
                'destaque'        => $semente % 23 === 0 ? 1 : 0,
                'ativo'           => 1,
                'vendas'          => $semente % 380,
            ]);
            $nProdutos++;
            $porCategoria[$cat['nome']]++;

            // -- Imagens (duas fotografias do conjunto da categoria) --
            $fotos = $cat['fotos'];
            foreach ([0, 1] as $ordem) {
                inserir('product_images', [
                    'id_product' => $idProduto,
                    'image'      => $foto($fotos[($i + $ordem) % count($fotos)]),
                    'principal'  => $ordem === 0 ? 1 : 0,
                    'ordem'      => $ordem,
                ]);
                $nImagens++;
            }

            // -- Opções (quantidade, acabamento, cor...) ----------
            foreach (catalogo_opcoes($slugCat, $preco) as $ordem => $op) {
                inserir('product_options', [
                    'id_product'   => $idProduto,
                    'grupo'        => $op['grupo'],
                    'valor'        => $op['valor'],
                    'ajuste_preco' => $op['ajuste'],
                    'ordem'        => $ordem,
                ]);
                $nOpcoes++;
            }
            $i++;
        }
    }

    return [
        'produtos apagados'   => $apagados['produtos'],
        'categorias apagadas' => $apagados['categorias'],
        'categorias criadas'  => $nCategorias,
        'produtos criados'    => $nProdutos,
        'imagens criadas'     => $nImagens,
        'opções criadas'      => $nOpcoes,
        'detalhe'             => implode(' · ', array_map(
            static fn ($n, $c) => "{$n}: {$c}",
            array_keys($porCategoria),
            $porCategoria
        )),
    ];
}

/**
 * Preço de arranque em MZN. Determinístico: o mesmo nome dá sempre
 * o mesmo preço, para o catálogo não mudar a cada nova execução.
 */
function catalogo_preco(string $categoria, string $nome, int $semente): float
{
    // Faixa mínima/máxima por categoria.
    $faixas = [
        'papelaria-personalizada'     => [250, 2500],
        'adesivos-rotulos-etiquetas'  => [150, 1800],
        'flyers-panfletos-folders'    => [300, 2200],
        'embalagens-sacolas'          => [400, 3500],
        'brindes-presentes-decoracao' => [350, 4500],
        'agendas-calendarios'         => [450, 2800],
        'banners-faixas-placas'       => [900, 6500],
        'catalogos-livros-revistas'   => [700, 5000],
    ];
    [$min, $max] = $faixas[$categoria] ?? [500, 3000];

    // Nos brindes o tipo de artigo pesa mais do que a categoria: uma caneta
    // e uma mochila não podem sair da mesma faixa de preço.
    $tipos = [
        [['Caneta', 'Lápis', 'Borracha', 'Régua', 'Botton', 'Chaveiro', 'Imã', 'Bolacha de Chopp',
          'Viseira', 'Balões', 'Bolinha', 'Porta Copos', 'Protetor de Webcam', 'Pingente'], 150, 700],
        [['Caneca', 'Copo', 'Squeeze', 'Xícara', 'Ecobag', 'Necessaire', 'Mouse Pad', 'Estojo',
          'Toalha', 'Chinelo', 'Pantufa', 'Avental', 'Azulejo', 'Marmita', 'Porta ', 'Máscara',
          'Almofada', 'Baralho', 'Descanso de Panela', 'Saca-rolhas', 'Cortador de Pizza'], 600, 2000],
        [['Mochila', 'Mala ', 'Mala para', 'Bolsa', 'Garrafa Térmica', 'Cooler', 'Guarda-Chuva',
          'Fone de Ouvido', 'Carregador', 'Caixa de Som', 'Mouse sem Fio', 'Kit ', 'Conjunto ',
          'Tapete', 'Capacho', 'Manta', 'Bola de', 'Óculos de Sol', 'Casinha para Gato'], 1800, 6000],
    ];
    if ($categoria === 'brindes-presentes-decoracao') {
        foreach ($tipos as [$termos, $tMin, $tMax]) {
            foreach ($termos as $termo) {
                if (str_contains($nome, $termo)) {
                    [$min, $max] = [$tMin, $tMax];
                    break 2;
                }
            }
        }
    }

    $preco = $min + ($semente % ($max - $min + 1));

    // Acabamentos especiais e linhas premium custam mais.
    foreach (['Hot Stamping' => 1.35, 'Markatto' => 1.30, 'Verniz' => 1.20, 'Premium' => 1.25] as $termo => $factor) {
        if (str_contains($nome, $termo)) {
            $preco *= $factor;
            break;
        }
    }
    // Linhas económicas custam menos.
    if (str_contains($nome, 'Econômic') || str_contains($nome, 'Básic')) {
        $preco *= 0.8;
    }

    // Arredondado às dezenas para parecer um preço de tabela.
    return (float) (round($preco / 50) * 50);
}

/**
 * Marca atribuída ao produto, conforme o tipo de acabamento ou material.
 *
 * @param  array<string,int>  $marcas  slug => id
 */
function catalogo_marca(string $nome, int $semente, array $marcas): ?int
{
    if ($marcas === []) {
        return null;
    }

    $eco = ['Ecológic', 'Reciclad', 'Bambu', 'Algodão', 'Kraft'];
    foreach ($eco as $termo) {
        if (str_contains($nome, $termo)) {
            return $marcas['ecoprint'] ?? null;
        }
    }

    foreach (['Hot Stamping', 'Markatto', 'Verniz', 'Premium', 'Deluxe'] as $termo) {
        if (str_contains($nome, $termo)) {
            return $marcas['lifei-premium'] ?? null;
        }
    }

    if (str_contains($nome, 'Econômic') || str_contains($nome, 'Básic')) {
        return $marcas['lifei-essential'] ?? null;
    }

    return $semente % 2 === 0
        ? ($marcas['lifei-essential'] ?? null)
        : ($marcas['lifei-premium'] ?? null);
}

/**
 * Opções configuráveis do produto (grupos mostrados na página do produto).
 * Os ajustes de preço são calculados a partir do preço base, para o salto
 * entre tiragens fazer sentido em qualquer produto.
 *
 * @return list<array{grupo:string,valor:string,ajuste:float}>
 */
function catalogo_opcoes(string $categoria, float $preco): array
{
    $ajuste = static fn (float $factor): float => (float) (round($preco * $factor / 50) * 50);

    // Grande formato: vende-se por tamanho, não por tiragem.
    if ($categoria === 'banners-faixas-placas') {
        return [
            ['grupo' => 'Tamanho', 'valor' => '1 × 1 m',  'ajuste' => 0.0],
            ['grupo' => 'Tamanho', 'valor' => '2 × 1 m',  'ajuste' => $ajuste(0.8)],
            ['grupo' => 'Tamanho', 'valor' => '3 × 1 m',  'ajuste' => $ajuste(1.6)],
            ['grupo' => 'Tamanho', 'valor' => '4 × 2 m',  'ajuste' => $ajuste(3.2)],
            ['grupo' => 'Acabamento', 'valor' => 'Ilhoses',           'ajuste' => 0.0],
            ['grupo' => 'Acabamento', 'valor' => 'Bainha e ilhoses',  'ajuste' => 250.0],
            ['grupo' => 'Acabamento', 'valor' => 'Estrutura roll-up', 'ajuste' => 1500.0],
        ];
    }

    // Brindes: tiragens curtas e personalização à escolha.
    if ($categoria === 'brindes-presentes-decoracao') {
        return [
            ['grupo' => 'Quantidade', 'valor' => '10 un.',  'ajuste' => 0.0],
            ['grupo' => 'Quantidade', 'valor' => '25 un.',  'ajuste' => $ajuste(1.8)],
            ['grupo' => 'Quantidade', 'valor' => '50 un.',  'ajuste' => $ajuste(3.4)],
            ['grupo' => 'Quantidade', 'valor' => '100 un.', 'ajuste' => $ajuste(6.0)],
            ['grupo' => 'Personalização', 'valor' => 'Impressão a 1 cor', 'ajuste' => 0.0],
            ['grupo' => 'Personalização', 'valor' => 'Impressão a cores', 'ajuste' => $ajuste(0.2)],
            ['grupo' => 'Personalização', 'valor' => 'Gravação a laser',  'ajuste' => $ajuste(0.35)],
        ];
    }

    // Impressos em geral: preço por tiragem + acabamento.
    return [
        ['grupo' => 'Quantidade', 'valor' => '100 un.',  'ajuste' => 0.0],
        ['grupo' => 'Quantidade', 'valor' => '250 un.',  'ajuste' => $ajuste(1.2)],
        ['grupo' => 'Quantidade', 'valor' => '500 un.',  'ajuste' => $ajuste(2.6)],
        ['grupo' => 'Quantidade', 'valor' => '1000 un.', 'ajuste' => $ajuste(4.5)],
        ['grupo' => 'Acabamento', 'valor' => 'Mate',       'ajuste' => 0.0],
        ['grupo' => 'Acabamento', 'valor' => 'Brilhante',  'ajuste' => 0.0],
        ['grupo' => 'Acabamento', 'valor' => 'Verniz UV',  'ajuste' => $ajuste(0.25)],
        ['grupo' => 'Acabamento', 'valor' => 'Laminação soft-touch', 'ajuste' => $ajuste(0.35)],
    ];
}

/** Frase curta mostrada no cartão do produto. */
function catalogo_descricao_curta(string $categoria, string $nome): string
{
    $frases = [
        'papelaria-personalizada'     => 'impressão personalizada com acabamento profissional.',
        'adesivos-rotulos-etiquetas'  => 'impressão em vinil ou papel adesivo, com corte à medida.',
        'flyers-panfletos-folders'    => 'impressão a cores frente e verso, com dobra incluída.',
        'embalagens-sacolas'          => 'embalagem personalizada com a imagem da sua marca.',
        'brindes-presentes-decoracao' => 'brinde personalizado com o seu logótipo.',
        'agendas-calendarios'         => 'personalização completa, capa a miolo.',
        'banners-faixas-placas'       => 'grande formato resistente, pronto a instalar.',
        'catalogos-livros-revistas'   => 'impressão e encadernação com acabamento de livraria.',
    ];

    $texto = $nome . ' — ' . ($frases[$categoria] ?? 'impressão personalizada.');

    return mb_substr($texto, 0, 255);
}

/** Descrição completa mostrada na página do produto. */
function catalogo_descricao(string $categoria, string $nome): string
{
    $textos = [
        'papelaria-personalizada' =>
            'impresso na Gráfica Lifei em papel de qualidade, com cores fiéis, corte preciso e acabamento cuidado. '
            . 'Escolha a gramagem, o acabamento e a quantidade — se não tiver a arte pronta, a nossa equipa de design trata dela.',
        'adesivos-rotulos-etiquetas' =>
            'impresso em material adesivo de qualidade, resistente ao manuseamento e à humidade. '
            . 'Disponível em corte redondo, quadrado, retangular ou à medida, em folha ou em rolo.',
        'flyers-panfletos-folders' =>
            'impresso a cores em frente e verso, com dobra e corte incluídos. '
            . 'Ideal para campanhas, promoções, eventos e distribuição em mão.',
        'embalagens-sacolas' =>
            'produzido em cartão ou papel resistente, com impressão personalizada da sua marca. '
            . 'Montagem simples e formato adequado ao transporte, à loja e ao envio por correio.',
        'brindes-presentes-decoracao' =>
            'personalizado com o seu logótipo em impressão ou gravação, conforme o material. '
            . 'Excelente para ofertas a clientes, eventos, feiras e campanhas internas.',
        'agendas-calendarios' =>
            'personalizado de capa a miolo, com a identidade da sua marca em todas as páginas. '
            . 'Uma oferta que acompanha o cliente durante todo o ano.',
        'banners-faixas-placas' =>
            'produzido em material resistente para uso interior ou exterior, com acabamento pronto a instalar. '
            . 'Indicado para lojas, feiras, obras, eventos e pontos de venda.',
        'catalogos-livros-revistas' =>
            'impresso e encadernado com acabamento profissional, à escolha entre capa mole, capa dura ou wire-o. '
            . 'Apoiamos também na paginação e na preparação dos ficheiros.',
    ];

    $comum = ' Enviamos sempre uma prova digital para aprovação antes de imprimir. '
        . 'Peça orçamento com a quantidade que precisa — o preço por unidade baixa com a tiragem.';

    return $nome . ' ' . ($textos[$categoria] ?? 'impresso na Gráfica Lifei.') . $comum;
}
