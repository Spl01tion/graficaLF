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

// Categorias para a barra de navegação rápida.
$categorias = query('SELECT * FROM categories ORDER BY nome') ?: [];

$titulo    = 'Impressão e Design Gráfico em Moçambique';
$descricao = 'Gráfica Lifei — impressão de qualidade em Maputo: cartões de visita, banners, t-shirts, stickers, catálogos e serviços de design.';
require __DIR__ . '/partials/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero text-white d-flex align-items-center" style="min-height: 88svh;">
    <span class="circulo c1"></span>
    <span class="circulo c2"></span>
    <span class="circulo c3"></span>

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
                <div class="row g-3">
                    <div class="col-6">
                        <div class="flutua d1 bg-white rounded-4 shadow-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=500&q=80" class="w-100" alt="T-shirt personalizada" style="height:180px;object-fit:cover;">
                        </div>
                    </div>
                    <div class="col-6 mt-5">
                        <div class="flutua d2 bg-white rounded-4 shadow-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1611532736579-6b16e2b50449?w=500&q=80" class="w-100" alt="Cartões de visita" style="height:180px;object-fit:cover;">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="flutua d3 bg-white rounded-4 shadow-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=500&q=80" class="w-100" alt="Roll-up banner" style="height:180px;object-fit:cover;">
                        </div>
                    </div>
                    <div class="col-6 mt-n5">
                        <div class="flutua d1 bg-white rounded-4 shadow-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1560343090-f0409e92791a?w=500&q=80" class="w-100" alt="Stickers" style="height:180px;object-fit:cover;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
                            <div><strong>Morada</strong><br><span class="text-muted small"><?= e(config_get('site_address', 'Maputo, Moçambique')) ?></span></div></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-2"><i class="bi bi-telephone-fill text-primary fs-5"></i>
                            <div><strong>Telefone</strong><br><span class="text-muted small"><?= e(config_get('site_phone', '+258 84 000 0000')) ?></span></div></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-2"><i class="bi bi-envelope-fill text-primary fs-5"></i>
                            <div><strong>Email</strong><br><span class="text-muted small"><?= e(config_get('site_email', 'geral@graficalifei.co.mz')) ?></span></div></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-2"><i class="bi bi-whatsapp text-primary fs-5"></i>
                            <div><strong>WhatsApp</strong><br>
                                <a href="<?= e(config_get('whatsapp_url', '#')) ?>" class="text-muted small text-decoration-none">Enviar mensagem</a></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
