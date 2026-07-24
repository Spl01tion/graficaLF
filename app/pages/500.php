<?php

/** 500.php — Página de erro interno (produção). */

$titulo    = 'Erro no servidor';
$descricao = 'Ocorreu um erro inesperado.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5">
    <div class="container py-5 text-center" style="min-height: 55svh;">
        <div class="display-1 fw-bold text-primary">500</div>
        <h1 class="h3 mt-3">Ups! Algo correu mal</h1>
        <p class="text-muted mb-4">Ocorreu um erro inesperado. Já fomos notificados e vamos resolvê-lo.</p>
        <a href="<?= url('home') ?>" class="btn btn-primary px-4"><i class="bi bi-house-door me-1"></i>Voltar ao início</a>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
