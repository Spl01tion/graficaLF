<?php

/**
 * conta.php — Área do cliente (perfil).
 *
 * Protegida por controlo_login(): só acessível com sessão iniciada.
 * O histórico de pedidos será ligado aqui no Módulo 6.
 */

controlo_login();

$titulo    = 'A minha conta';
$descricao = 'A sua área pessoal na Gráfica Lifei.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-4" style="max-width: 720px;">
        <h1 class="h3 fw-bold mb-4">A minha conta</h1>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="logo-badge" style="width:56px;height:56px;font-size:1.4rem;">
                        <?= e(mb_strtoupper(mb_substr(user('nome'), 0, 1))) ?>
                    </span>
                    <div>
                        <h2 class="h5 mb-0"><?= e(user('nome')) ?></h2>
                        <span class="badge bg-secondary"><?= e(user('role') === 'admin' ? 'Administrador' : 'Cliente') ?></span>
                    </div>
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8"><?= e(user('email')) ?></dd>

                    <dt class="col-sm-4 text-muted">Telefone</dt>
                    <dd class="col-sm-8"><?= e(user('telefone') ?: '—') ?></dd>
                </dl>
            </div>
        </div>

        <?php if (eh_admin()): ?>
            <a href="<?= url('admin') ?>" class="btn btn-primary">
                <i class="bi bi-speedometer2 me-1"></i>Ir para o painel de administração
            </a>
        <?php endif; ?>

        <?php
        $meusPedidos = query(
            'SELECT * FROM orders WHERE id_user = :u ORDER BY created_at DESC',
            ['u' => user('id_user')]
        ) ?: [];
        ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Os meus pedidos</h2>
                <?php if ($meusPedidos === []): ?>
                    <p class="text-muted mb-0">Ainda não fez nenhum pedido. <a href="<?= url('shop') ?>">Ver produtos</a>.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Referência</th><th>Data</th><th>Total</th><th>Estado</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meusPedidos as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($p['referencia']) ?></td>
                                        <td class="small text-muted"><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                                        <td><?= e(moeda($p['total'])) ?></td>
                                        <td><span class="badge bg-<?= estado_cor($p['status']) ?>"><?= e(estado_pedido($p['status'])) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
