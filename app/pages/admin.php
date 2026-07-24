<?php

/**
 * admin.php — Painel de administração (esboço).
 *
 * MÓDULO 2: por agora existe apenas para demonstrar o RBAC —
 * controlo_admin() exige sessão iniciada E perfil de administrador.
 * O dashboard completo (KPIs, CRUDs) é construído no Módulo 7.
 */

controlo_admin();

$titulo    = 'Painel';
$descricao = 'Administração da Gráfica Lifei.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light" style="min-height: 60svh;">
    <div class="container py-4">
        <div class="d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-speedometer2 fs-3 text-primary"></i>
            <h1 class="h3 fw-bold mb-0">Painel de Administração</h1>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>
            Acesso concedido — o RBAC está a funcionar. Este é um esboço:
            o painel completo (KPIs, produtos, categorias, marcas, pedidos,
            cupões, utilizadores, definições, mensagens) é construído no
            <strong>Módulo 7</strong>.
        </div>

        <p>Olá, <strong><?= e(user('nome')) ?></strong>. Está autenticado como administrador.</p>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
