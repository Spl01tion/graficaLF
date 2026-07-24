<?php

/** 404.php — Página não encontrada. */

$titulo    = 'Página não encontrada';
$descricao = 'A página que procura não existe.';

require __DIR__ . '/partials/header.php';
?>

<section class="py-5">
    <div class="container py-5 text-center" style="min-height: 55svh;">
        <div class="display-1 fw-bold text-primary">404</div>
        <h1 class="h3 mt-3">Ups! Página não encontrada</h1>
        <p class="text-muted mb-4">A página que procura não existe ou foi movida.</p>
        <a href="<?= url('home') ?>" class="btn btn-primary px-4">
            <i class="bi bi-house-door me-1"></i>Voltar ao início
        </a>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
