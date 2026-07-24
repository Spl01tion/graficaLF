<?php

/**
 * sobre.php — Página institucional "Sobre".
 */

$titulo    = 'Sobre nós';
$descricao = 'Conheça a Gráfica Lifei — a sua parceira de impressão e design em Maputo, Moçambique.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-dark-blue text-white">
    <div class="container py-4 text-center">
        <h1 class="fw-bold mb-2">Sobre a Gráfica Lifei</h1>
        <p class="text-white-50 mb-0">Criatividade e qualidade ao serviço da sua marca.</p>
    </div>
</section>

<section class="py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-revelar>
                <img src="https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=800&q=80"
                     alt="Equipa Gráfica Lifei" class="img-fluid rounded-4 shadow-sm">
            </div>
            <div class="col-lg-6" data-revelar>
                <h2 class="fw-bold mb-3">Quem somos</h2>
                <p class="text-muted">
                    A Gráfica Lifei é uma gráfica moderna sediada em Maputo, dedicada a transformar
                    ideias em produtos impressos de alta qualidade. Trabalhamos com empresas e
                    particulares, oferecendo desde cartões de visita a grandes banners, sempre
                    com o mesmo compromisso: excelência em cada detalhe.
                </p>
                <p class="text-muted">
                    Combinamos equipamento profissional, materiais de primeira e uma equipa criativa
                    que acompanha cada projecto do conceito à entrega.
                </p>
            </div>
        </div>

        <!-- Missão / Visão / Valores -->
        <div class="row g-4 mt-4">
            <?php
            $mvv = [
                ['icon' => 'bi-bullseye', 'titulo' => 'Missão', 'texto' => 'Dar vida às ideias dos nossos clientes com impressão de qualidade e um serviço próximo.'],
                ['icon' => 'bi-eye', 'titulo' => 'Visão', 'texto' => 'Ser a gráfica de referência em Moçambique pela criatividade e pela confiança.'],
                ['icon' => 'bi-heart', 'titulo' => 'Valores', 'texto' => 'Qualidade, rigor, criatividade e compromisso com cada prazo.'],
            ];
            foreach ($mvv as $m): ?>
                <div class="col-md-4" data-revelar>
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <span class="icone-circulo mb-3"><i class="bi <?= $m['icon'] ?>"></i></span>
                            <h3 class="h5 fw-bold"><?= e($m['titulo']) ?></h3>
                            <p class="text-muted mb-0"><?= e($m['texto']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Números -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="row text-center g-4">
            <?php
            $numeros = [
                ['n' => '10+', 'l' => 'Anos de experiência'],
                ['n' => '5.000+', 'l' => 'Projectos impressos'],
                ['n' => '1.200+', 'l' => 'Clientes satisfeitos'],
                ['n' => '24h', 'l' => 'Resposta rápida'],
            ];
            foreach ($numeros as $x): ?>
                <div class="col-6 col-md-3" data-revelar>
                    <div class="display-5 fw-bold text-primary"><?= e($x['n']) ?></div>
                    <p class="text-muted mb-0"><?= e($x['l']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container py-4 text-center">
        <h2 class="fw-bold mb-3">Vamos trabalhar juntos?</h2>
        <p class="text-muted mb-4">Peça já o seu orçamento — sem compromisso.</p>
        <a href="<?= url('contacto') ?>" class="btn btn-primary btn-lg px-4">Pedir orçamento</a>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
