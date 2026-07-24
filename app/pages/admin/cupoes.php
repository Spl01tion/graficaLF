<?php

/**
 * admin/cupoes.php — CRUD de cupões de desconto.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'guardar') {
        $id     = (int) ($_POST['id'] ?? 0);
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo   = ($_POST['tipo'] ?? 'percentual') === 'fixo' ? 'fixo' : 'percentual';
        $valor  = (float) ($_POST['valor'] ?? 0);

        if ($codigo === '' || $valor <= 0) {
            flash('erro', 'Código e valor são obrigatórios.');
        } elseif (query_row('SELECT id_coupon FROM coupons WHERE codigo=:c AND id_coupon<>:id', ['c' => $codigo, 'id' => $id])) {
            flash('erro', 'Já existe um cupão com este código.');
        } else {
            $dados = [
                'codigo'     => $codigo,
                'tipo'       => $tipo,
                'valor'      => $valor,
                'minimo'     => (float) ($_POST['minimo'] ?? 0),
                'limite_uso' => ($_POST['limite_uso'] ?? '') !== '' ? (int) $_POST['limite_uso'] : null,
                'inicia_em'  => ($_POST['inicia_em'] ?? '') !== '' ? $_POST['inicia_em'] : null,
                'expira_em'  => ($_POST['expira_em'] ?? '') !== '' ? $_POST['expira_em'] : null,
                'ativo'      => isset($_POST['ativo']) ? 1 : 0,
            ];
            if ($id > 0) {
                execute('UPDATE coupons SET codigo=:codigo, tipo=:tipo, valor=:valor, minimo=:minimo,
                         limite_uso=:limite_uso, inicia_em=:inicia_em, expira_em=:expira_em, ativo=:ativo
                         WHERE id_coupon=:id', $dados + ['id' => $id]);
                flash('sucesso', 'Cupão actualizado.');
            } else {
                inserir('coupons', $dados);
                flash('sucesso', 'Cupão criado.');
            }
        }
    } elseif ($acao === 'apagar') {
        execute('DELETE FROM coupons WHERE id_coupon=:id', ['id' => (int) $_POST['id']]);
        flash('sucesso', 'Cupão apagado.');
    }

    redirect('admin/cupoes');
}

$cupoes = query('SELECT * FROM coupons ORDER BY created_at DESC') ?: [];

$tituloPainel = 'Cupões';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($cupoes) ?> cupão(ões)</p>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCupao" onclick="prepararCupao()"><i class="bi bi-plus-lg me-1"></i>Novo cupão</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Código</th><th>Desconto</th><th>Mínimo</th><th>Uso</th><th>Validade</th><th>Estado</th><th class="text-end">Acções</th></tr></thead>
            <tbody>
                <?php foreach ($cupoes as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($c['codigo']) ?></td>
                        <td><?= $c['tipo'] === 'percentual' ? e((float) $c['valor']) . '%' : e(moeda($c['valor'])) ?></td>
                        <td><?= e(moeda($c['minimo'])) ?></td>
                        <td><?= (int) $c['usado'] ?><?= $c['limite_uso'] !== null ? ' / ' . (int) $c['limite_uso'] : '' ?></td>
                        <td class="small"><?= $c['expira_em'] ? e(date('d/m/Y', strtotime($c['expira_em']))) : '—' ?></td>
                        <td><span class="badge bg-<?= $c['ativo'] ? 'success' : 'secondary' ?>"><?= $c['ativo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary" onclick='prepararCupao(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#modalCupao"><i class="bi bi-pencil"></i></button>
                            <form method="post" action="<?= url('admin/cupoes') ?>" class="d-inline" onsubmit="return confirm('Apagar este cupão?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="apagar">
                                <input type="hidden" name="id" value="<?= (int) $c['id_coupon'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($cupoes === []): ?><tr><td colspan="7" class="text-center text-muted">Sem cupões.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCupao" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= url('admin/cupoes') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="acao" value="guardar">
            <input type="hidden" name="id" id="cup-id">
            <div class="modal-header">
                <h5 class="modal-title" id="cup-titulo">Novo cupão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Código</label><input type="text" name="codigo" id="cup-codigo" class="form-control text-uppercase" required></div>
                    <div class="col-md-6"><label class="form-label">Tipo</label>
                        <select name="tipo" id="cup-tipo" class="form-select">
                            <option value="percentual">Percentual (%)</option>
                            <option value="fixo">Valor fixo (MZN)</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Valor</label><input type="number" step="0.01" name="valor" id="cup-valor" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Pedido mínimo (MZN)</label><input type="number" step="0.01" name="minimo" id="cup-minimo" class="form-control" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Limite de usos</label><input type="number" name="limite_uso" id="cup-limite" class="form-control" placeholder="Ilimitado"></div>
                    <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" class="form-check-input" name="ativo" id="cup-ativo" checked><label class="form-check-label" for="cup-ativo">Activo</label></div></div>
                    <div class="col-md-6"><label class="form-label">Início</label><input type="date" name="inicia_em" id="cup-inicia" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Expira</label><input type="date" name="expira_em" id="cup-expira" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function prepararCupao(c = null) {
        document.getElementById('cup-id').value     = c ? c.id_coupon : '';
        document.getElementById('cup-codigo').value = c ? c.codigo : '';
        document.getElementById('cup-tipo').value   = c ? c.tipo : 'percentual';
        document.getElementById('cup-valor').value  = c ? c.valor : '';
        document.getElementById('cup-minimo').value = c ? c.minimo : '0';
        document.getElementById('cup-limite').value = c && c.limite_uso ? c.limite_uso : '';
        document.getElementById('cup-inicia').value = c && c.inicia_em ? c.inicia_em : '';
        document.getElementById('cup-expira').value = c && c.expira_em ? c.expira_em : '';
        document.getElementById('cup-ativo').checked = c ? c.ativo == 1 : true;
        document.getElementById('cup-titulo').textContent = c ? 'Editar cupão' : 'Novo cupão';
    }
</script>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
