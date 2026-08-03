<?php

/**
 * home.php — Página inicial (landing page).
 *
 * Junta as secções pedidas no enunciado. Os produtos "mais vendidos"
 * são carregados dinamicamente da base de dados (coluna `vendas`).
 */

// Produtos mais vendidos (com a imagem principal) — Secção 3.
$maisVendidos = query(
    'SELECT p.*,
            (SELECT image FROM product_images i WHERE i.id_product = p.id_product
             ORDER BY principal DESC, ordem LIMIT 1) AS imagem
     FROM products p
     WHERE p.ativo = 1
     ORDER BY p.vendas DESC
     LIMIT 8'
) ?: [];

// Categorias (com o nº de produtos activos) para o mosaico de navegação.
$categorias = query(
    'SELECT c.*,
            (SELECT COUNT(*) FROM products p
              WHERE p.id_category = c.id_category AND p.ativo = 1) AS n
     FROM categories c
     ORDER BY c.nome
     LIMIT 8'
) ?: [];

// Imagens do mosaico de categorias (assets/img/catCards). A chave é o slug
// da categoria; se não houver correspondência usa-se a imagem da BD.
$catCards = [
    'papelaria-personalizada'     => 'Papelaria_Personalizada.webp',
    'adesivos-rotulos-etiquetas'  => 'Adesivos_Rotulos_Etiquetas.webp',
    'flyers-panfletos-folders'    => 'Flyers_Panfletos_Folders.webp',
    'embalagens-sacolas'          => 'Embalagens_Sacolas.webp',
    'brindes-presentes-decoracao' => 'Brindes_Presentes_Decoracao.webp',
    'agendas-calendarios'         => 'Agendas_Calendarios.webp',
    'banners-faixas-placas'       => 'Banners_Faixas_Placas.webp',
    'catalogos-livros-revistas'   => 'Catalogos_Livros_Revistas.webp',
];

$titulo    = 'Impressão e Design Gráfico em Moçambique';
$descricao = 'Gráfica Lifei — impressão de qualidade em Maputo: cartões de visita, banners, t-shirts, stickers, catálogos e serviços de design.';
require __DIR__ . '/partials/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero text-white d-flex align-items-center" style="min-height: 88svh;">
    <span class="circulo c1"></span>
    <span class="circulo c2"></span>
    <span class="circulo c3"></span>

    <div id="heroCarousel" class="carousel slide w-100" data-bs-ride="carousel" data-bs-interval="6000">
        <div class="carousel-inner">

            <!-- Slide 1 -->
            <div class="carousel-item active">
                <div class="container position-relative py-5">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill">
                                <i class="bi bi-star-fill me-1"></i>Impressão &amp; Design em Maputo
                            </span>
                            <h1 class="display-3 fw-bold mb-3" data-revelar>
                                Damos vida às suas <span class="text-warning">ideias</span> impressas
                            </h1>
                            <p class="lead text-white-50 mb-4" data-revelar style="max-width: 520px;">
                                Cartões de visita, banners, t-shirts, stickers, catálogos e muito mais —
                                com qualidade profissional e entrega rápida.
                            </p>
                            <div class="d-flex gap-3 flex-wrap" data-revelar>
                                <a href="<?= url('shop') ?>" class="btn btn-primary btn-lg px-4">
                                    <i class="bi bi-bag me-1"></i>Ver Produtos
                                </a>
                                <a href="<?= url('contacto') ?>" class="btn btn-outline-light btn-lg px-4">
                                    Pedir Orçamento
                                </a>
                            </div>
                        </div>

                        <!-- Objetos flutuantes (mockups) -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="hero-mockups">
                                <img src="<?= asset('img/mockups/MenHoodie.webp') ?>" class="flutua d2" alt="Hoodie personalizado" style="width:58%; top:0; left:0; transform:rotate(0deg); z-index:1;">
                                <img src="<?= asset('img/mockups/tshirt-mockup.webp') ?>" class="flutua d1" alt="T-shirt personalizada" style="width:100%; top:5%; right:-20%; transform:rotate(0deg); z-index:2;">
                                <img src="<?= asset('img/mockups/mug_mockup.webp') ?>" class="flutua d3" alt="Caneca personalizada" style="width:54%; bottom:0; left:-5%; transform:rotate(0deg); z-index:4;">
                                <img src="<?= asset('img/mockups/CapMockup.webp') ?>" class="flutua d2" alt="Boné personalizado" style="width:52%; bottom:6%; right:12%; transform:rotate(0deg); z-index:3;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="carousel-item">
                <div class="container position-relative py-5">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill">
                                <i class="bi bi-flag-fill me-1"></i>Materiais Promocionais
                            </span>
                            <h1 class="display-3 fw-bold mb-3">
                                Dê <span class="text-warning">visibilidade</span> à sua marca
                            </h1>
                            <p class="lead text-white-50 mb-4" style="max-width: 520px;">
                                Bandeiras, banners, roll-ups e catálogos personalizados —
                                ideais para eventos, lojas e apresentações.
                            </p>
                            <div class="d-flex gap-3 flex-wrap">
                                <a href="<?= url('shop') ?>" class="btn btn-primary btn-lg px-4">
                                    <i class="bi bi-bag me-1"></i>Ver Produtos
                                </a>
                                <a href="<?= url('contacto') ?>" class="btn btn-outline-light btn-lg px-4">
                                    Pedir Orçamento
                                </a>
                            </div>
                        </div>

                        <!-- Objetos flutuantes (mockups) — mesma disposição, novos produtos -->
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="hero-mockups">
                                <img src="<?= asset('img/mockups/bandeira.webp') ?>" class="flutua d2" alt="Bandeira personalizada" style="width:58%; top:0; left:0; transform:rotate(0deg); z-index:1;">
                                <img src="<?= asset('img/mockups/banner.webp') ?>" class="flutua d1" alt="Banner personalizado" style="width:100%; top:5%; right:-20%; transform:rotate(0deg); z-index:2;">
                                <img src="<?= asset('img/mockups/bookcatalogs.webp') ?>" class="flutua d3" alt="Catálogo personalizado" style="width:54%; bottom:0; left:-5%; transform:rotate(0deg); z-index:4;">
                                <img src="<?= asset('img/mockups/rollup.webp') ?>" class="flutua d2" alt="Roll-up personalizado" style="width:52%; bottom:6%; right:12%; transform:rotate(0deg); z-index:3;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Seguinte</span>
        </button>

        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        </div>
    </div>
</section>

<!-- ============ FAIXA DE CONFIANÇA (sobrepõe o hero) ============ -->
<div class="container faixa-confianca">
    <div class="card border-0 shadow-lg">
        <div class="card-body p-0">
            <div class="row g-0 text-center text-md-start">
                <?php
                $garantias = [
                    ['icon' => 'bi-truck',          'titulo' => 'Entrega em 48h',      'texto' => 'Em Maputo e arredores'],
                    ['icon' => 'bi-brush',          'titulo' => 'Design incluído',     'texto' => 'A nossa equipa trata da arte'],
                    ['icon' => 'bi-chat-square-text', 'titulo' => 'Orçamento grátis',  'texto' => 'Resposta em poucas horas'],
                    ['icon' => 'bi-patch-check',    'titulo' => 'Qualidade garantida', 'texto' => 'Prova antes de imprimir'],
                ];
                foreach ($garantias as $g): ?>
                    <div class="col-6 col-lg-3 faixa-confianca__item">
                        <div class="d-flex flex-column flex-md-row align-items-center gap-md-3 p-4">
                            <i class="bi <?= $g['icon'] ?> fs-2 text-primary mb-2 mb-md-0"></i>
                            <div>
                                <div class="fw-bold small"><?= e($g['titulo']) ?></div>
                                <div class="text-muted small"><?= e($g['texto']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============ MOSAICO DE CATEGORIAS ============ -->
<?php if ($categorias): ?>
    <section class="py-5">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-end mb-4" data-revelar>
                <div>
                    <span class="etiqueta-seccao">Catálogo</span>
                    <h2 class="fw-bold mb-1 mt-2">Explore por categoria</h2>
                    <p class="text-muted mb-0">Escolha o que quer imprimir e nós tratamos do resto.</p>
                </div>
                <a href="<?= url('shop') ?>" class="btn btn-outline-primary d-none d-sm-inline-block">Ver a loja</a>
            </div>

            <div class="bento-cat" data-revelar>
                <?php foreach ($categorias as $c): ?>
                    <?php
                    $capa = isset($catCards[$c['slug']])
                        ? asset('img/catCards/' . $catCards[$c['slug']])
                        : imagem_url($c['image']);
                    ?>
                    <a class="cat-tile" href="<?= url('shop') ?>?categoria=<?= urlencode($c['slug']) ?>">
                        <img src="<?= e($capa) ?>" alt="<?= e($c['nome']) ?>" loading="lazy">
                        <div class="cat-tile__conteudo">
                            <span class="cat-tile__contador"><?= (int) $c['n'] ?> produto<?= (int) $c['n'] === 1 ? '' : 's' ?></span>
                            <h3 class="cat-tile__titulo"><?= e($c['nome']) ?></h3>
                            <p class="cat-tile__texto"><?= e($c['descricao'] ?? '') ?></p>
                        </div>
                        <span class="cat-tile__seta"><i class="bi bi-arrow-right"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4 d-sm-none">
                <a href="<?= url('shop') ?>" class="btn btn-outline-primary">Ver a loja</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- ============ SECÇÃO 1 — Como concretizar as suas ideias ============ -->
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5" data-revelar>
            <h2 class="fw-bold">Como concretizar as suas ideias com a Gráfica Lifei</h2>
            <p class="text-muted">Três passos simples até ao produto final.</p>
        </div>
        <div class="row g-4 text-center">
            <?php
            $passos = [
                ['n' => 1, 'icon' => 'bi-grid', 'titulo' => 'Selecione o produto', 'texto' => 'Escolha entre dezenas de produtos de impressão e personalização.'],
                ['n' => 2, 'icon' => 'bi-brush', 'titulo' => 'Defina o design', 'texto' => 'Envie a sua arte ou peça ajuda à nossa equipa de design.'],
                ['n' => 3, 'icon' => 'bi-truck', 'titulo' => 'Deixe o resto connosco', 'texto' => 'Imprimimos com qualidade e entregamos onde precisar.'],
            ];
            foreach ($passos as $p): ?>
                <div class="col-md-4" data-revelar>
                    <div class="p-4 h-100">
                        <div class="position-relative d-inline-block mb-3">
                            <span class="icone-circulo"><i class="bi <?= $p['icon'] ?>"></i></span>
                            <span class="passo-num position-absolute top-0 start-100 translate-middle" style="width:32px;height:32px;font-size:.9rem;"><?= $p['n'] ?></span>
                        </div>
                        <h3 class="h5 fw-bold"><?= e($p['titulo']) ?></h3>
                        <p class="text-muted mb-0"><?= e($p['texto']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ MARQUEE ============ -->
<div class="marquee">
    <div class="marquee__track">
        <?php $termos = ['Notepads', 'Flyers', 'Livros', 'Calendários', 'Postais', 'Roll-up Banners', 'Cartões de Visita', 'Catálogos', 'Stickers', 'Cartazes', 'T-shirts', 'Bonés'];
        // Duplicado para o loop ser contínuo.
        for ($i = 0; $i < 2; $i++): ?>
            <?php foreach ($termos as $t): ?><span><?= e($t) ?><i class="bi bi-asterisk"></i></span><?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>

<!-- ============ NÚMEROS (contadores animados) ============ -->
<section class="faixa-numeros text-white py-5">
    <div class="container py-4">
        <div class="row g-4 text-center">
            <?php
            $numeros = [
                ['valor' => 10,   'sufixo' => '+',  'rotulo' => 'Anos de experiência'],
                ['valor' => 5000, 'sufixo' => '+',  'rotulo' => 'Trabalhos impressos'],
                ['valor' => 1200, 'sufixo' => '+',  'rotulo' => 'Clientes satisfeitos'],
                ['valor' => 48,   'sufixo' => 'h',  'rotulo' => 'Prazo médio de entrega'],
            ];
            foreach ($numeros as $n): ?>
                <div class="col-6 col-lg-3" data-revelar>
                    <div class="numero-grande">
                        <span data-contador="<?= (int) $n['valor'] ?>">0</span><?= e($n['sufixo']) ?>
                    </div>
                    <div class="text-white-50 small text-uppercase" style="letter-spacing:.08em;"><?= e($n['rotulo']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ SECÇÃO 2 — Motivos / benefícios ============ -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5" data-revelar>
            <h2 class="fw-bold">Motivos para começar a imprimir connosco</h2>
            <p class="text-muted">Qualidade, rapidez e apoio de verdade.</p>
        </div>
        <div class="row g-4">
            <?php
            $beneficios = [
                ['icon' => 'bi-award', 'titulo' => 'Qualidade premium', 'texto' => 'Equipamento profissional e materiais de primeira.'],
                ['icon' => 'bi-lightning-charge', 'titulo' => 'Entrega rápida', 'texto' => 'Prazos curtos sem comprometer o resultado.'],
                ['icon' => 'bi-palette', 'titulo' => 'Design incluído', 'texto' => 'A nossa equipa ajuda a criar a sua arte.'],
                ['icon' => 'bi-cash-coin', 'titulo' => 'Preços justos', 'texto' => 'Orçamentos transparentes e competitivos.'],
                ['icon' => 'bi-recycle', 'titulo' => 'Opções ecológicas', 'texto' => 'Materiais sustentáveis quando possível.'],
                ['icon' => 'bi-headset', 'titulo' => 'Apoio dedicado', 'texto' => 'Acompanhamos o seu pedido do início ao fim.'],
            ];
            foreach ($beneficios as $b): ?>
                <div class="col-md-6 col-lg-4" data-revelar>
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex gap-3 p-4">
                            <span class="icone-circulo flex-shrink-0"><i class="bi <?= $b['icon'] ?>"></i></span>
                            <div>
                                <h3 class="h6 fw-bold mb-1"><?= e($b['titulo']) ?></h3>
                                <p class="text-muted small mb-0"><?= e($b['texto']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ SECÇÃO 3 — Produtos mais vendidos ============ -->
<section class="py-5">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-end mb-4" data-revelar>
            <div>
                <h2 class="fw-bold mb-1">Produtos mais vendidos</h2>
                <p class="text-muted mb-0">Os favoritos dos nossos clientes.</p>
            </div>
            <a href="<?= url('shop') ?>" class="btn btn-outline-primary">Ver tudo</a>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
            <?php foreach ($maisVendidos as $produto): ?>
                <div class="col" data-revelar><?= parcial('card-produto', ['produto' => $produto]) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ SECÇÃO 4 — O que diferencia ============ -->
<section class="py-5 bg-dark-blue text-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-5" data-revelar>
                <h2 class="fw-bold mb-3">O que diferencia a Gráfica Lifei</h2>
                <p class="text-white-50">
                    Mais do que uma gráfica: um parceiro criativo que trata cada projecto
                    como se fosse o seu cartão de visita.
                </p>
                <a href="<?= url('sobre') ?>" class="btn btn-outline-light mt-2">Conheça-nos</a>
            </div>
            <div class="col-lg-7">
                <div class="row g-4">
                    <?php
                    $difs = [
                        ['icon' => 'bi-check2-circle', 'titulo' => 'Acabamentos exclusivos', 'texto' => 'Verniz UV, laminação e cortes especiais.'],
                        ['icon' => 'bi-people', 'titulo' => 'Equipa experiente', 'texto' => 'Anos a servir empresas e particulares em Maputo.'],
                        ['icon' => 'bi-arrow-repeat', 'titulo' => 'Repetição fácil', 'texto' => 'Guardamos os seus trabalhos para reimpressão.'],
                        ['icon' => 'bi-shield-check', 'titulo' => 'Satisfação garantida', 'texto' => 'Revemos as provas consigo antes de imprimir.'],
                    ];
                    foreach ($difs as $d): ?>
                        <div class="col-sm-6" data-revelar>
                            <div class="d-flex gap-3">
                                <i class="bi <?= $d['icon'] ?> fs-3 text-warning"></i>
                                <div>
                                    <h3 class="h6 fw-bold mb-1"><?= e($d['titulo']) ?></h3>
                                    <p class="text-white-50 small mb-0"><?= e($d['texto']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ TESTEMUNHOS ============ -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5" data-revelar>
            <span class="etiqueta-seccao">Testemunhos</span>
            <h2 class="fw-bold mt-2 mb-1">O que dizem os nossos clientes</h2>
            <p class="text-muted mb-0">Marcas moçambicanas que já imprimem connosco.</p>
        </div>
        <div class="row g-4">
            <?php
            $testemunhos = [
                ['nome' => 'Anabela Sitoe',  'cargo' => 'Boutique Kanimambo',   'texto' => 'Precisávamos de 500 cartões para uma feira em dois dias. Ficaram prontos a tempo e com uma qualidade acima do que esperávamos.'],
                ['nome' => 'Jorge Macuácua', 'cargo' => 'Construções JM, Lda.', 'texto' => 'Fizeram o design dos uniformes e dos roll-ups da empresa. Acompanharam tudo até à entrega, sem precisarmos de andar atrás.'],
                ['nome' => 'Telma Nhaca',    'cargo' => 'Café da Baixa',        'texto' => 'Os menus e os stickers ficaram lindíssimos. Já é a terceira vez que repetimos a encomenda — guardam os ficheiros e é imediato.'],
            ];
            foreach ($testemunhos as $t): ?>
                <div class="col-md-6 col-lg-4" data-revelar>
                    <figure class="card-testemunho card border-0 shadow-sm h-100 mb-0">
                        <div class="card-body p-4">
                            <div class="text-warning mb-3" aria-label="5 em 5 estrelas">
                                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                            </div>
                            <blockquote class="mb-4"><p class="mb-0"><?= e($t['texto']) ?></p></blockquote>
                            <figcaption class="d-flex align-items-center gap-3 mt-auto">
                                <span class="avatar-inicial"><?= e(mb_substr($t['nome'], 0, 1)) ?></span>
                                <div>
                                    <div class="fw-bold small"><?= e($t['nome']) ?></div>
                                    <div class="text-muted small"><?= e($t['cargo']) ?></div>
                                </div>
                            </figcaption>
                        </div>
                    </figure>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ FAIXA DE CAMPANHA ============ -->
<section class="py-5">
    <div class="container py-2">
        <div class="faixa-campanha text-white p-4 p-lg-5" data-revelar>
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill">
                        <i class="bi bi-gift-fill me-1"></i>Primeira encomenda
                    </span>
                    <h2 class="fw-bold mb-2">10% de desconto no seu primeiro pedido</h2>
                    <p class="mb-0 text-white-50">
                        Use o código abaixo ao finalizar a encomenda ou mencione-o quando pedir o orçamento.
                    </p>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <button type="button" class="cupao-codigo" data-copiar="LIFEI10">
                        <span>LIFEI10</span><i class="bi bi-clipboard ms-2"></i>
                    </button>
                    <div class="mt-3">
                        <a href="<?= url('shop') ?>" class="btn btn-light btn-lg px-4">Começar a encomendar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ SECÇÃO DE CONTACTO ============ -->
<section class="py-5" id="contacto">
    <div class="container py-4">
        <div class="row g-5">
            <div class="col-lg-6" data-revelar>
                <h2 class="fw-bold mb-3">Fale connosco</h2>
                <p class="text-muted mb-4">Peça um orçamento sem compromisso. Respondemos rapidamente.</p>

                <?php require __DIR__ . '/partials/form-contacto.php'; ?>
            </div>

            <div class="col-lg-6" data-revelar>
                <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm mb-4">
                    <iframe src="<?= e(config_get('maps_embed', 'https://www.google.com/maps?q=Maputo&output=embed')) ?>"
                        style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex gap-2"><i class="bi bi-geo-alt-fill text-primary fs-5"></i>
                            <div><strong>Morada</strong><br><span class="text-muted small"><?= e(config_get('site_address', 'Maputo, Moçambique')) ?></span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-2"><i class="bi bi-telephone-fill text-primary fs-5"></i>
                            <div><strong>Telefone</strong><br><span class="text-muted small"><?= e(config_get('site_phone', '+258 84 000 0000')) ?></span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-2"><i class="bi bi-envelope-fill text-primary fs-5"></i>
                            <div><strong>Email</strong><br><span class="text-muted small"><?= e(config_get('site_email', 'geral@graficalifei.co.mz')) ?></span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-2"><i class="bi bi-whatsapp text-primary fs-5"></i>
                            <div><strong>WhatsApp</strong><br>
                                <a href="<?= e(config_get('whatsapp_url', '#')) ?>" class="text-muted small text-decoration-none">Enviar mensagem</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>