<?php

/**
 * admin/pedidos.php — Gestão de pedidos.
 *
 *   /admin/pedidos          -> lista
 *   /admin/pedidos?ver=ID   -> detalhe + alteração de estado + histórico
 */

$estados = ['pendente', 'confirmado', 'em_producao', 'pronto', 'entregue', 'cancelado'];

// ---- Controlador (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($acao === 'estado') {
        $novo = $_POST['status'] ?? '';
        $nota = trim($_POST['nota'] ?? '');
        if (in_array($novo, $estados, true)) {
            execute('UPDATE orders SET status=:s WHERE id_order=:id', ['s' => $novo, 'id' => $id]);
            inserir('order_status_history', [
                'id_order' => $id, 'status' => $novo,
                'nota' => $nota ?: null, 'id_user' => user('id_user'),
            ]);
            flash('sucesso', 'Estado actualizado para "' . estado_pedido($novo) . '".');
        }
    } elseif ($acao === 'reenviar') {
        $ped = query_row('SELECT * FROM orders WHERE id_order=:id', ['id' => $id]);
        if ($ped) {
            $corpo = template_email('Actualização do seu pedido — ' . e($ped['referencia']), '
                <p>Olá ' . e($ped['cliente_nome']) . ',</p>
                <p>O seu pedido <strong>' . e($ped['referencia']) . '</strong> encontra-se no estado:
                <strong>' . e(estado_pedido($ped['status'])) . '</strong>.</p>
                <p>Total: ' . e(moeda($ped['total'])) . '</p>');
            enviar_email($ped['cliente_email'], $ped['cliente_nome'], 'Pedido ' . $ped['referencia'] . ' — ' . estado_pedido($ped['status']), $corpo);
            flash('sucesso', 'Email de actualização reenviado ao cliente.');
        }
    } elseif ($acao === 'apagar') {
        execute('DELETE FROM orders WHERE id_order=:id', ['id' => $id]);
        flash('sucesso', 'Pedido apagado.');
        redirect('admin/pedidos');
    }

    redirect('admin/pedidos?ver=' . $id);
}

// ==================================================================
//  DETALHE
// ==================================================================
if (isset($_GET['ver'])) {
    $ped = query_row('SELECT * FROM orders WHERE id_order=:id', ['id' => (int) $_GET['ver']]);
    if (! $ped) { redirect('admin/pedidos'); }

    $itens     = query('SELECT * FROM order_items WHERE id_order=:id', ['id' => $ped['id_order']]) ?: [];
    $historico = query('SELECT h.*, u.nome AS user_nome FROM order_status_history h LEFT JOIN users u ON u.id_user=h.id_user WHERE h.id_order=:id ORDER BY h.created_at DESC', ['id' => $ped['id_order']]) ?: [];

    $tituloPainel = 'Pedido ' . $ped['referencia'];
    require __DIR__ . '/../partials/admin_header.php';
    ?>
    <a href="<?= url('admin/pedidos') ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Voltar</a>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-0"><?= e($ped['referencia']) ?></h2>
                            <span class="text-muted small"><?= e(date('d/m/Y H:i', strtotime($ped['created_at']))) ?></span>
                        </div>
                        <span class="badge bg-<?= estado_cor($ped['status']) ?> fs-6"><?= e(estado_pedido($ped['status'])) ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Produto</th><th>Qtd</th><th class="text-end">Subtotal</th></tr></thead>
                            <tbody>
                                <?php foreach ($itens as $it): ?>
                                    <tr>
                                        <td><?= e($it['produto_nome']) ?><?php if ($it['opcoes']): ?><br><small class="text-muted"><?= e($it['opcoes']) ?></small><?php endif; ?></td>
                                        <td><?= (int) $it['quantidade'] ?></td>
                                        <td class="text-end"><?= e(moeda($it['subtotal'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr><td colspan="2" class="text-end">Subtotal</td><td class="text-end"><?= e(moeda($ped['subtotal'])) ?></td></tr>
                                <?php if ($ped['desconto'] > 0): ?><tr><td colspan="2" class="text-end text-success">Desconto (<?= e($ped['coupon_codigo']) ?>)</td><td class="text-end text-success">-<?= e(moeda($ped['desconto'])) ?></td></tr><?php endif; ?>
                                <tr class="fw-bold"><td colspan="2" class="text-end">Total</td><td class="text-end"><?= e(moeda($ped['total'])) ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Histórico -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Histórico de estados</h2>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($historico as $h): ?>
                            <li class="d-flex gap-3 mb-3">
                                <span class="badge bg-<?= estado_cor($h['status']) ?>"><?= e(estado_pedido($h['status'])) ?></span>
                                <div class="small">
                                    <div class="text-muted"><?= e(date('d/m/Y H:i', strtotime($h['created_at']))) ?><?= $h['user_nome'] ? ' · ' . e($h['user_nome']) : '' ?></div>
                                    <?php if ($h['nota']): ?><div><?= e($h['nota']) ?></div><?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Cliente</h2>
                    <p class="mb-1"><i class="bi bi-person me-2"></i><?= e($ped['cliente_nome']) ?></p>
                    <p class="mb-1"><i class="bi bi-envelope me-2"></i><a href="mailto:<?= e($ped['cliente_email']) ?>"><?= e($ped['cliente_email']) ?></a></p>
                    <p class="mb-1"><i class="bi bi-telephone me-2"></i><?= e($ped['cliente_telefone']) ?></p>
                    <hr>
                    <p class="mb-1"><strong><?= $ped['tipo_entrega'] === 'entrega' ? 'Entrega' : 'Levantamento na loja' ?></strong></p>
                    <?php if ($ped['endereco']): ?><p class="small text-muted mb-1"><?= e($ped['endereco']) ?></p><?php endif; ?>
                    <?php if ($ped['observacoes']): ?><hr><p class="small mb-0"><strong>Obs.:</strong> <?= nl2br(e($ped['observacoes'])) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Alterar estado</h2>
                    <form method="post" action="<?= url('admin/pedidos') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="acao" value="estado">
                        <input type="hidden" name="id" value="<?= (int) $ped['id_order'] ?>">
                        <select name="status" class="form-select mb-2">
                            <?php foreach ($estados as $s): ?>
                                <option value="<?= $s ?>" <?= $ped['status'] === $s ? 'selected' : '' ?>><?= e(estado_pedido($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="nota" class="form-control mb-2" placeholder="Nota (opcional)">
                        <button class="btn btn-primary w-100">Actualizar estado</button>
                    </form>
                </div>
            </div>

            <form method="post" action="<?= url('admin/pedidos') ?>" class="mb-2">
                <?= csrf_field() ?>
                <input type="hidden" name="acao" value="reenviar">
                <input type="hidden" name="id" value="<?= (int) $ped['id_order'] ?>">
                <button class="btn btn-outline-primary w-100"><i class="bi bi-envelope me-1"></i>Reenviar email ao cliente</button>
            </form>
            <form method="post" action="<?= url('admin/pedidos') ?>" onsubmit="return confirm('Apagar este pedido?')">
                <?= csrf_field() ?>
                <input type="hidden" name="acao" value="apagar">
                <input type="hidden" name="id" value="<?= (int) $ped['id_order'] ?>">
                <button class="btn btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Apagar pedido</button>
            </form>
        </div>
    </div>
    <?php
    require __DIR__ . '/../partials/admin_footer.php';
    return;
}

// ==================================================================
//  LISTA
// ==================================================================
$filtroEstado = $_GET['estado'] ?? '';
$where = '';
$params = [];
if (in_array($filtroEstado, $estados, true)) {
    $where = 'WHERE status = :s';
    $params['s'] = $filtroEstado;
}
$pedidos = query("SELECT * FROM orders {$where} ORDER BY created_at DESC", $params) ?: [];

$tituloPainel = 'Pedidos';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <p class="text-muted mb-0"><?= count($pedidos) ?> pedido(s)</p>
    <div class="btn-group btn-group-sm">
        <a href="<?= url('admin/pedidos') ?>" class="btn btn-outline-secondary <?= $filtroEstado === '' ? 'active' : '' ?>">Todos</a>
        <?php foreach ($estados as $s): ?>
            <a href="<?= url('admin/pedidos?estado=' . $s) ?>" class="btn btn-outline-secondary <?= $filtroEstado === $s ? 'active' : '' ?>"><?= e(estado_pedido($s)) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Ref.</th><th>Cliente</th><th>Data</th><th>Total</th><th>Estado</th><th class="text-end">Acções</th></tr></thead>
            <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($p['referencia']) ?></td>
                        <td><?= e($p['cliente_nome']) ?><br><span class="small text-muted"><?= e($p['cliente_email']) ?></span></td>
                        <td class="small"><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                        <td><?= e(moeda($p['total'])) ?></td>
                        <td><span class="badge bg-<?= estado_cor($p['status']) ?>"><?= e(estado_pedido($p['status'])) ?></span></td>
                        <td class="text-end"><a href="<?= url('admin/pedidos?ver=' . (int) $p['id_order']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($pedidos === []): ?><tr><td colspan="6" class="text-center text-muted">Sem pedidos.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
