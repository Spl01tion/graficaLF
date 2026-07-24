<?php

/**
 * home.php — Página inicial.
 *
 * MÓDULO 0: por agora é apenas uma base que confirma que o layout
 * (header + footer) e o sistema de rotas funcionam. O conteúdo real
 * da landing page (hero, secções, produtos mais vendidos) entra no
 * Módulo 3.
 */

$titulo    = 'Impressão e Design Gráfico em Moçambique';
$descricao = 'Gráfica Lifei — a sua parceira de impressão em Maputo.';

require __DIR__ . '/partials/header.php';
?>

<section class="py-5" style="background: linear-gradient(135deg, var(--gl-azul), var(--gl-slate)); min-height: 70svh;">
    <div class="container py-5 text-white text-center d-flex flex-column justify-content-center" style="min-height: 60svh;">
        <span class="badge bg-warning text-dark align-self-center mb-3 px-3 py-2 rounded-pill">
            <i class="bi bi-check-circle-fill me-1"></i>Fundação (Módulo 0) instalada com sucesso
        </span>
        <h1 class="display-4 fw-bold mb-3" data-revelar>
            Damos vida às suas <span class="text-warning">ideias</span> impressas
        </h1>
        <p class="lead text-white-50 mb-4 mx-auto" style="max-width: 640px;" data-revelar>
            Cartões de visita, banners, t-shirts, stickers, catálogos e muito mais.
            Estrutura base pronta — o conteúdo completo desta página chega no Módulo 3.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap" data-revelar>
            <a href="<?= url('shop') ?>" class="btn btn-primary btn-lg px-4">Ver Produtos</a>
            <a href="<?= url('contacto') ?>" class="btn btn-outline-light btn-lg px-4">Pedir Orçamento</a>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4" data-revelar>
                <i class="bi bi-1-circle-fill fs-1 text-primary"></i>
                <h5 class="mt-3">Selecione o produto</h5>
                <p class="text-muted">Escolha entre dezenas de produtos de impressão.</p>
            </div>
            <div class="col-md-4" data-revelar>
                <i class="bi bi-2-circle-fill fs-1 text-primary"></i>
                <h5 class="mt-3">Defina o design</h5>
                <p class="text-muted">Envie a sua arte ou peça ajuda à nossa equipa.</p>
            </div>
            <div class="col-md-4" data-revelar>
                <i class="bi bi-3-circle-fill fs-1 text-primary"></i>
                <h5 class="mt-3">Deixe o resto connosco</h5>
                <p class="text-muted">Imprimimos e entregamos com qualidade garantida.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
