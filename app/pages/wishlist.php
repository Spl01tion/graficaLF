<?php

/**
 * wishlist.php — Lista de desejos + endpoint de toggle.
 *
 *   POST ?acao=toggle  id_produto   -> adiciona/remove (JSON no AJAX)
 *   GET                              -> página com os produtos favoritos
 */

$acao = $_GET['acao'] ?? null;
$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'toggle') {
    csrf_verificar();

    $id = (int) ($_POST['id_produto'] ?? 0);
    $adicionado = wishlist_toggle($id);

    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'         => true,
            'adicionado' => $adicionado,
            'contador'   => wishlist_qtd(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    flash('sucesso', $adicionado ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.');
    redirect('wishlist');
}

$produtos  = wishlist_produtos();
$titulo    = 'Lista de desejos';
$descricao = 'Os seus produtos favoritos.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4"><i class="bi bi-heart me-2"></i>Lista de desejos</h1>

        <?php if ($produtos === []): ?>
            <div class="text-center py-5">
                <i class="bi bi-heart display-4 text-muted"></i>
                <p class="text-muted mt-3">Ainda não guardou nenhum produto.</p>
                <a href="<?= url('shop') ?>" class="btn btn-primary">Explorar produtos</a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <?php foreach ($produtos as $produto): ?>
                    <div class="col"><?= parcial('card-produto', ['produto' => $produto]) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
