<?php

/**
 * shop.php — Loja: listagem (com pesquisa/filtros) OU página de produto.
 *
 * O router coloca em $url[1] o segundo segmento do caminho:
 *   /shop            -> listagem
 *   /shop/{slug}     -> página do produto com esse slug
 *
 * Toda a construção de SQL usa marcadores (prepared statements); os
 * valores dos filtros nunca são concatenados directamente.
 */

$slug = $url[1] ?? null;

// ==================================================================
//  PÁGINA DE PRODUTO INDIVIDUAL
// ==================================================================
if ($slug !== null && $slug !== '') {

    $produto = query_row(
        'SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug, b.nome AS marca_nome
         FROM products p
         LEFT JOIN categories c ON c.id_category = p.id_category
         LEFT JOIN brands b ON b.id_brand = p.id_brand
         WHERE p.slug = :slug AND p.ativo = 1 LIMIT 1',
        ['slug' => $slug]
    );

    if (! $produto) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        return;
    }

    $idProduto = (int) $produto['id_product'];

    $imagens = query('SELECT * FROM product_images WHERE id_product = :id ORDER BY principal DESC, ordem', ['id' => $idProduto]) ?: [];

    // Opções agrupadas (Tamanho, Cor, ...) para os selectores.
    $opcoesRaw = query('SELECT * FROM product_options WHERE id_product = :id ORDER BY grupo, ordem', ['id' => $idProduto]) ?: [];
    $opcoes = [];
    foreach ($opcoesRaw as $o) {
        $opcoes[$o['grupo']][] = $o;
    }

    // Produtos relacionados (mesma categoria).
    $relacionados = query(
        'SELECT p.*, (SELECT image FROM product_images i WHERE i.id_product = p.id_product ORDER BY principal DESC, ordem LIMIT 1) AS imagem
         FROM products p
         WHERE p.id_category = :cat AND p.id_product <> :id AND p.ativo = 1
         ORDER BY p.vendas DESC LIMIT 4',
        ['cat' => $produto['id_category'], 'id' => $idProduto]
    ) ?: [];

    $temPromo = ! empty($produto['preco_promo']) && (float) $produto['preco_promo'] < (float) $produto['preco'];
    $precoBase = $temPromo ? (float) $produto['preco_promo'] : (float) $produto['preco'];

    $titulo    = $produto['nome'];
    $descricao = $produto['descricao_curta'] ?? ('Compre ' . $produto['nome'] . ' na Gráfica Lifei.');
    require __DIR__ . '/partials/header.php';
    ?>

    <section class="py-4">
        <div class="container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= url('home') ?>" class="text-decoration-none">Início</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('shop') ?>" class="text-decoration-none">Shop</a></li>
                    <?php if ($produto['categoria_slug']): ?>
                        <li class="breadcrumb-item"><a href="<?= url('shop?categoria=' . $produto['categoria_slug']) ?>" class="text-decoration-none"><?= e($produto['categoria_nome']) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?= e($produto['nome']) ?></li>
                </ol>
            </nav>

            <div class="row g-4 g-lg-5">
                <!-- Galeria -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm overflow-hidden mb-3">
                        <img id="img-principal" src="<?= e(imagem_url($imagens[0]['image'] ?? null)) ?>"
                             alt="<?= e($produto['nome']) ?>" class="w-100" style="aspect-ratio:1;object-fit:cover;">
                    </div>
                    <?php if (count($imagens) > 1): ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($imagens as $im): ?>
                                <img src="<?= e(imagem_url($im['image'])) ?>" alt="" role="button"
                                     class="rounded border object-fit-cover thumb-galeria" style="width:72px;height:72px;"
                                     onclick="document.getElementById('img-principal').src=this.src">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Informação + opções -->
                <div class="col-lg-6">
                    <?php if ($produto['marca_nome']): ?>
                        <span class="text-muted small text-uppercase"><?= e($produto['marca_nome']) ?></span>
                    <?php endif; ?>
                    <h1 class="fw-bold h2 mb-2"><?= e($produto['nome']) ?></h1>

                    <div class="mb-3">
                        <?php if ($temPromo): ?>
                            <span class="text-decoration-line-through text-muted me-2"><?= e(moeda($produto['preco'])) ?></span>
                            <span class="fs-3 fw-bold text-primary" id="preco-base" data-preco="<?= e($precoBase) ?>"><?= e(moeda($precoBase)) ?></span>
                            <span class="badge bg-primary ms-2">Promoção</span>
                        <?php else: ?>
                            <span class="fs-3 fw-bold text-primary" id="preco-base" data-preco="<?= e($precoBase) ?>"><?= e(moeda($precoBase)) ?></span>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted"><?= nl2br(e($produto['descricao'] ?? $produto['descricao_curta'] ?? '')) ?></p>

                    <!-- Opções configuráveis -->
                    <?php foreach ($opcoes as $grupo => $valores): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small mb-2"><?= e($grupo) ?></label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($valores as $k => $v):
                                    $id = 'op_' . $v['id_option'];
                                    $ajuste = (float) $v['ajuste_preco']; ?>
                                    <input type="radio" class="btn-check opcao-produto" name="grupo_<?= e(str_to_url($grupo)) ?>"
                                           id="<?= $id ?>" value="<?= (int) $v['id_option'] ?>"
                                           data-ajuste="<?= e($ajuste) ?>" <?= $k === 0 ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary btn-sm" for="<?= $id ?>">
                                        <?= e($v['valor']) ?><?php if ($ajuste > 0): ?> <span class="text-muted">(+<?= e(moeda($ajuste)) ?>)</span><?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Quantidade + adicionar -->
                    <div class="d-flex align-items-center gap-3 mt-4">
                        <div class="input-group" style="width:130px;">
                            <button class="btn btn-outline-secondary" type="button" onclick="ajustarQtd(-1)">−</button>
                            <input type="number" id="qtd-produto" class="form-control text-center" value="1" min="1" max="9999">
                            <button class="btn btn-outline-secondary" type="button" onclick="ajustarQtd(1)">+</button>
                        </div>
                        <button class="btn btn-primary flex-grow-1 btn-add-carrinho"
                                data-produto="<?= $idProduto ?>" id="btn-add-detalhe">
                            <i class="bi bi-bag-plus me-1"></i>Adicionar ao carrinho
                        </button>
                        <button class="btn btn-outline-primary btn-wishlist <?= wishlist_tem($idProduto) ? 'ativo' : '' ?>"
                                data-produto="<?= $idProduto ?>" title="Favoritos">
                            <i class="bi <?= wishlist_tem($idProduto) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                        </button>
                    </div>

                    <ul class="list-inline small text-muted mt-4 mb-0">
                        <li class="list-inline-item me-3"><i class="bi bi-truck me-1"></i>Entrega em Maputo</li>
                        <li class="list-inline-item me-3"><i class="bi bi-shield-check me-1"></i>Qualidade garantida</li>
                        <li class="list-inline-item"><i class="bi bi-chat-dots me-1"></i>Apoio dedicado</li>
                    </ul>
                </div>
            </div>

            <!-- Relacionados -->
            <?php if ($relacionados !== []): ?>
                <div class="mt-5 pt-4 border-top">
                    <h2 class="h4 fw-bold mb-4">Produtos relacionados</h2>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                        <?php foreach ($relacionados as $rel): ?>
                            <div class="col"><?= parcial('card-produto', ['produto' => $rel]) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Sincroniza a quantidade do input com o botão "adicionar".
        const inputQtd = document.getElementById('qtd-produto');
        const btnAdd = document.getElementById('btn-add-detalhe');
        function ajustarQtd(d) {
            let v = parseInt(inputQtd.value || '1', 10) + d;
            inputQtd.value = Math.max(1, v);
            btnAdd.dataset.quantidade = inputQtd.value;
        }
        inputQtd.addEventListener('change', () => { btnAdd.dataset.quantidade = Math.max(1, parseInt(inputQtd.value||'1',10)); });
        btnAdd.dataset.quantidade = '1';

        // Actualiza o preço mostrado conforme as opções escolhidas.
        const precoEl = document.getElementById('preco-base');
        const precoBase = parseFloat(precoEl.dataset.preco);
        function fmtMzn(v){ return v.toLocaleString('pt-PT',{minimumFractionDigits:2,maximumFractionDigits:2}).replace(/\s/g,'.') + ' MZN'; }
        function recalcularPreco() {
            let total = precoBase;
            document.querySelectorAll('.opcao-produto:checked').forEach(o => total += parseFloat(o.dataset.ajuste || '0'));
            precoEl.textContent = fmtMzn(total);
        }
        document.querySelectorAll('.opcao-produto').forEach(o => o.addEventListener('change', recalcularPreco));
        recalcularPreco();
    </script>

    <?php
    require __DIR__ . '/partials/footer.php';
    return;
}

// ==================================================================
//  LISTAGEM DA LOJA (pesquisa + filtros + ordenação + paginação)
// ==================================================================

$pesquisa   = trim($_GET['q'] ?? '');
$fCategoria = trim($_GET['categoria'] ?? '');
$fMarca     = trim($_GET['marca'] ?? '');
$precoMin   = $_GET['preco_min'] ?? '';
$precoMax   = $_GET['preco_max'] ?? '';
$ordenar    = $_GET['ordenar'] ?? 'vendas';

// Construção dinâmica do WHERE com marcadores.
$where  = ['p.ativo = 1'];
$params = [];

if ($pesquisa !== '') {
    $where[] = '(p.nome LIKE :q OR p.descricao_curta LIKE :q2)';
    $params['q']  = '%' . $pesquisa . '%';
    $params['q2'] = '%' . $pesquisa . '%';
}
if ($fCategoria !== '') {
    $where[] = 'c.slug = :cat';
    $params['cat'] = $fCategoria;
}
if ($fMarca !== '') {
    $where[] = 'b.slug = :marca';
    $params['marca'] = $fMarca;
}
if (is_numeric($precoMin)) {
    $where[] = 'COALESCE(p.preco_promo, p.preco) >= :pmin';
    $params['pmin'] = (float) $precoMin;
}
if (is_numeric($precoMax)) {
    $where[] = 'COALESCE(p.preco_promo, p.preco) <= :pmax';
    $params['pmax'] = (float) $precoMax;
}

$whereSql = implode(' AND ', $where);

// Ordenação (lista branca — nunca vem directamente do utilizador para o SQL).
$ordens = [
    'vendas'      => 'p.vendas DESC',
    'recentes'    => 'p.created_at DESC',
    'preco_asc'   => 'COALESCE(p.preco_promo, p.preco) ASC',
    'preco_desc'  => 'COALESCE(p.preco_promo, p.preco) DESC',
    'nome'        => 'p.nome ASC',
];
$orderSql = $ordens[$ordenar] ?? $ordens['vendas'];

// Paginação.
$porPagina = 9;
$pagina    = max(1, (int) ($_GET['page'] ?? 1));
$offset    = ($pagina - 1) * $porPagina;

$totalRow = query_row(
    "SELECT COUNT(*) AS total FROM products p
     LEFT JOIN categories c ON c.id_category = p.id_category
     LEFT JOIN brands b ON b.id_brand = p.id_brand
     WHERE {$whereSql}",
    $params
);
$total = (int) ($totalRow['total'] ?? 0);
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$produtos = query(
    "SELECT p.*, c.nome AS categoria_nome,
            (SELECT image FROM product_images i WHERE i.id_product = p.id_product ORDER BY principal DESC, ordem LIMIT 1) AS imagem
     FROM products p
     LEFT JOIN categories c ON c.id_category = p.id_category
     LEFT JOIN brands b ON b.id_brand = p.id_brand
     WHERE {$whereSql}
     ORDER BY {$orderSql}
     LIMIT {$porPagina} OFFSET {$offset}",
    $params
) ?: [];

$categorias = query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.id_category=c.id_category AND p.ativo=1) AS n FROM categories c ORDER BY c.nome') ?: [];
$marcas     = query('SELECT * FROM brands ORDER BY nome') ?: [];

/** Gera um link de filtro preservando os restantes parâmetros. */
function shop_link(array $novos): string
{
    $q = array_merge($_GET, $novos);
    unset($q['url'], $q['page']);
    $q = array_filter($q, static fn ($v) => $v !== '' && $v !== null);
    return url('shop') . ($q ? '?' . http_build_query($q) : '');
}

$titulo    = 'Shop';
$descricao = 'Loja online da Gráfica Lifei — produtos de impressão e personalização.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-4 bg-light border-bottom">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Loja</h1>
                <p class="text-muted mb-0 small"><?= $total ?> produto(s) encontrado(s)</p>
            </div>
            <form method="get" action="<?= url('shop') ?>" class="d-flex gap-2" style="max-width:420px;">
                <input type="search" name="q" class="form-control" placeholder="Pesquisar produtos..." value="<?= e($pesquisa) ?>">
                <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>
</section>

<section class="py-4">
    <div class="container">
        <div class="row g-4">
            <!-- Filtros -->
            <aside class="col-lg-3">
                <button class="btn btn-outline-secondary w-100 d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#filtros">
                    <i class="bi bi-funnel me-1"></i>Filtros
                </button>
                <div class="offcanvas-lg offcanvas-start" id="filtros">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Filtros</h5>
                        <button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#filtros"></button>
                    </div>
                    <div class="offcanvas-body d-block">
                        <!-- Categorias -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Categorias</h6>
                                <a href="<?= shop_link(['categoria' => '']) ?>" class="d-block text-decoration-none py-1 <?= $fCategoria === '' ? 'text-primary fw-semibold' : 'text-dark' ?>">Todas</a>
                                <?php foreach ($categorias as $c): ?>
                                    <a href="<?= shop_link(['categoria' => $c['slug']]) ?>"
                                       class="d-flex justify-content-between text-decoration-none py-1 <?= $fCategoria === $c['slug'] ? 'text-primary fw-semibold' : 'text-dark' ?>">
                                        <span><?= e($c['nome']) ?></span><span class="text-muted small"><?= (int) $c['n'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Marcas -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Marcas</h6>
                                <a href="<?= shop_link(['marca' => '']) ?>" class="d-block text-decoration-none py-1 <?= $fMarca === '' ? 'text-primary fw-semibold' : 'text-dark' ?>">Todas</a>
                                <?php foreach ($marcas as $b): ?>
                                    <a href="<?= shop_link(['marca' => $b['slug']]) ?>"
                                       class="d-block text-decoration-none py-1 <?= $fMarca === $b['slug'] ? 'text-primary fw-semibold' : 'text-dark' ?>"><?= e($b['nome']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Preço -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Faixa de preço (MZN)</h6>
                                <form method="get" action="<?= url('shop') ?>">
                                    <?php foreach (['q' => $pesquisa, 'categoria' => $fCategoria, 'marca' => $fMarca, 'ordenar' => $ordenar] as $k => $v): ?>
                                        <?php if ($v !== ''): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; ?>
                                    <?php endforeach; ?>
                                    <div class="d-flex gap-2 mb-2">
                                        <input type="number" name="preco_min" class="form-control form-control-sm" placeholder="Min" value="<?= e(is_numeric($precoMin) ? $precoMin : '') ?>">
                                        <input type="number" name="preco_max" class="form-control form-control-sm" placeholder="Max" value="<?= e($precoMax) ?>">
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary w-100">Aplicar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Grelha -->
            <div class="col-lg-9">
                <!-- Ordenação -->
                <div class="d-flex justify-content-end mb-3">
                    <form method="get" action="<?= url('shop') ?>" class="d-flex align-items-center gap-2">
                        <?php foreach (['q' => $pesquisa, 'categoria' => $fCategoria, 'marca' => $fMarca] as $k => $v): ?>
                            <?php if ($v !== ''): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; ?>
                        <?php endforeach; ?>
                        <label class="small text-muted">Ordenar:</label>
                        <select name="ordenar" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                            <option value="vendas"     <?= $ordenar === 'vendas' ? 'selected' : '' ?>>Mais vendidos</option>
                            <option value="recentes"   <?= $ordenar === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                            <option value="preco_asc"  <?= $ordenar === 'preco_asc' ? 'selected' : '' ?>>Preço: menor primeiro</option>
                            <option value="preco_desc" <?= $ordenar === 'preco_desc' ? 'selected' : '' ?>>Preço: maior primeiro</option>
                            <option value="nome"       <?= $ordenar === 'nome' ? 'selected' : '' ?>>Nome (A-Z)</option>
                        </select>
                    </form>
                </div>

                <?php if ($produtos === []): ?>
                    <div class="estado-vazio">
                        <div class="estado-vazio__icone mb-4"><i class="bi bi-search"></i></div>
                        <h2 class="h5 fw-bold mb-2">Nenhum produto encontrado</h2>
                        <p class="text-muted mb-4">Experimente outros termos de pesquisa ou remova alguns filtros.</p>
                        <a href="<?= url('shop') ?>" class="btn btn-outline-primary px-4">Limpar filtros</a>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4">
                        <?php foreach ($produtos as $produto): ?>
                            <div class="col"><?= parcial('card-produto', ['produto' => $produto]) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginação -->
                    <?php if ($totalPaginas > 1): ?>
                        <?php
                        // Link de uma página, mantendo os filtros activos.
                        $linkPagina = static function (int $n): string {
                            $base = shop_link([]);
                            return $base . (str_contains($base, '?') ? '&' : '?') . 'page=' . $n;
                        };
                        ?>
                        <nav class="mt-5" aria-label="Navegação de páginas">
                            <ul class="pagination justify-content-center flex-wrap">
                                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $linkPagina(max(1, $pagina - 1)) ?>" aria-label="Página anterior">
                                        <i class="bi bi-chevron-left"></i><span class="d-none d-sm-inline ms-1">Anterior</span>
                                    </a>
                                </li>

                                <?php foreach (paginacao_numeros($pagina, $totalPaginas) as $n): ?>
                                    <?php if ($n === '…'): ?>
                                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                    <?php else: ?>
                                        <li class="page-item <?= $n === $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= $linkPagina($n) ?>"
                                               <?= $n === $pagina ? 'aria-current="page"' : '' ?>><?= $n ?></a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $linkPagina(min($totalPaginas, $pagina + 1)) ?>" aria-label="Página seguinte">
                                        <span class="d-none d-sm-inline me-1">Seguinte</span><i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                            <p class="text-center text-muted small mb-0">
                                Página <?= $pagina ?> de <?= $totalPaginas ?> &middot; <?= $total ?> produto(s)
                            </p>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
