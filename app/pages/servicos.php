<?php

/**
 * servicos.php — Página de serviços.
 * Secções alternadas (imagem/texto) com CTA para orçamento.
 */

$servicos = [
    [
        'titulo' => 'Impressão geral',
        'texto'  => 'Cartões de visita, flyers, cartazes, catálogos, calendários e muito mais — impressão a cores de alta qualidade em vários formatos e gramagens.',
        'img'    => 'https://images.unsplash.com/photo-1503694978374-8a2fa686963a?w=700&q=80',
        'icon'   => 'bi-printer',
    ],
    [
        'titulo' => 'Design de Banner & Impressão',
        'texto'  => 'Banners em lona, roll-ups e telas de grande formato para eventos, feiras e pontos de venda. Do conceito à instalação.',
        'img'    => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=700&q=80',
        'icon'   => 'bi-easel',
    ],
    [
        'titulo' => 'Impressão de Capas de Livros',
        'texto'  => 'Capas duras e moles, encadernação profissional e acabamentos de luxo para livros, teses, portfólios e edições especiais.',
        'img'    => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=700&q=80',
        'icon'   => 'bi-book',
    ],
    [
        'titulo' => 'Serviços de Design',
        'texto'  => 'A nossa equipa criativa desenvolve a sua arte: logótipos, identidade visual, layouts para impressão e materiais promocionais.',
        'img'    => 'https://images.unsplash.com/photo-1524234107056-1c1f48f64ab8?w=700&q=80',
        'icon'   => 'bi-palette',
    ],
    [
        'titulo' => 'Estratégias de Branding',
        'texto'  => 'Ajudamos a construir e a fortalecer a sua marca — do manual de identidade à aplicação consistente em todos os materiais.',
        'img'    => 'https://images.unsplash.com/photo-1611926653458-09294b3142bf?w=700&q=80',
        'icon'   => 'bi-stars',
    ],
];

$titulo    = 'Serviços';
$descricao = 'Serviços de impressão e design da Gráfica Lifei: impressão geral, banners, capas de livros, design e branding.';
require __DIR__ . '/partials/header.php';
?>

<!-- Cabeçalho -->
<section class="py-5 bg-dark-blue text-white">
    <div class="container py-4 text-center">
        <h1 class="fw-bold mb-2">Os nossos serviços</h1>
        <p class="text-white-50 mb-0">Soluções completas de impressão e design para a sua marca.</p>
    </div>
</section>

<!-- Secções alternadas -->
<section class="py-5">
    <div class="container py-4">
        <?php foreach ($servicos as $i => $s): ?>
            <div class="row align-items-center g-4 g-lg-5 <?= $i > 0 ? 'mt-2' : '' ?> mb-5 flex-lg-row<?= $i % 2 ? '-reverse' : '' ?>" data-revelar>
                <div class="col-lg-6">
                    <img src="<?= e($s['img']) ?>" alt="<?= e($s['titulo']) ?>" class="servico-img shadow-sm">
                </div>
                <div class="col-lg-6">
                    <span class="icone-circulo mb-3"><i class="bi <?= $s['icon'] ?>"></i></span>
                    <h2 class="fw-bold"><?= e($s['titulo']) ?></h2>
                    <p class="text-muted"><?= e($s['texto']) ?></p>
                    <a href="<?= url('contacto') ?>" class="btn btn-primary mt-2">
                        <i class="bi bi-chat-dots me-1"></i>Pedir orçamento
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA final -->
<section class="py-5 bg-light">
    <div class="container py-4 text-center">
        <h2 class="fw-bold mb-3">Não encontrou o que procura?</h2>
        <p class="text-muted mb-4">Fazemos trabalhos personalizados. Conte-nos a sua ideia.</p>
        <a href="<?= url('contacto') ?>" class="btn btn-primary btn-lg px-4">Fale connosco</a>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
