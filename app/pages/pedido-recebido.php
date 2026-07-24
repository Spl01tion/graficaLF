<?php

/**
 * pedido-recebido.php — Página de confirmação após o pedido.
 * Mostra a referência guardada na sessão pelo fazer-pedido.php.
 */

$referencia = $_SESSION['ultimo_pedido'] ?? null;

// Sem referência (acesso directo), volta à loja.
if (! $referencia) {
    redirect('shop');
}
unset($_SESSION['ultimo_pedido']);

$titulo    = 'Pedido recebido';
$descricao = 'O seu pedido foi recebido com sucesso.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5">
    <div class="container py-5 text-center" style="max-width: 640px;">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4.5rem;"></i>
        </div>
        <h1 class="fw-bold mb-2">Pedido recebido!</h1>
        <p class="text-muted mb-4">
            Obrigado pela sua preferência. Entraremos em contacto brevemente para
            combinar o pagamento e a entrega.
        </p>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <span class="text-muted small d-block mb-1">Referência do pedido</span>
                <span class="fs-3 fw-bold text-primary"><?= e($referencia) ?></span>
            </div>
        </div>

        <p class="small text-muted mb-4">
            <i class="bi bi-envelope me-1"></i>Enviámos um email de confirmação com o resumo do pedido.
        </p>

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= url('shop') ?>" class="btn btn-primary px-4"><i class="bi bi-bag me-1"></i>Continuar a comprar</a>
            <a href="<?= url('home') ?>" class="btn btn-outline-secondary px-4">Voltar ao início</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
