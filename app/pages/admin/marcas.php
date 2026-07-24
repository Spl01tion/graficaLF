<?php

/**
 * admin/marcas.php — CRUD de marcas.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'guardar') {
        $id   = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            flash('erro', 'O nome é obrigatório.');
        } else {
            $slug = str_to_url($nome);
            $existe = query_row('SELECT id_brand FROM brands WHERE slug=:s AND id_brand<>:id', ['s' => $slug, 'id' => $id]);
            if ($existe) {
                $slug .= '-' . substr(md5((string) microtime()), 0, 4);
            }

            if ($id > 0) {
                execute('UPDATE brands SET nome=:n, slug=:s WHERE id_brand=:id', ['n' => $nome, 's' => $slug, 'id' => $id]);
                flash('sucesso', 'Marca actualizada.');
            } else {
                inserir('brands', ['nome' => $nome, 'slug' => $slug]);
                flash('sucesso', 'Marca criada.');
            }
        }
    } elseif ($acao === 'apagar') {
        execute('DELETE FROM brands WHERE id_brand = :id', ['id' => (int) $_POST['id']]);
        flash('sucesso', 'Marca apagada.');
    }

    redirect('admin/marcas');
}

$marcas = query('SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.id_brand=b.id_brand) AS n FROM brands b ORDER BY b.nome') ?: [];

$tituloPainel = 'Marcas';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($marcas) ?> marca(s)</p>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMarca"
            onclick="prepararMarca()"><i class="bi bi-plus-lg me-1"></i>Nova marca</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Nome</th><th>Slug</th><th>Produtos</th><th class="text-end">Acções</th></tr></thead>
            <tbody>
                <?php foreach ($marcas as $b): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($b['nome']) ?></td>
                        <td class="small text-muted"><?= e($b['slug']) ?></td>
                        <td><span class="badge bg-secondary"><?= (int) $b['n'] ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"
                                onclick='prepararMarca(<?= json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                data-bs-toggle="modal" data-bs-target="#modalMarca"><i class="bi bi-pencil"></i></button>
                            <form method="post" action="<?= url('admin/marcas') ?>" class="d-inline" onsubmit="return confirm('Apagar esta marca?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="apagar">
                                <input type="hidden" name="id" value="<?= (int) $b['id_brand'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($marcas === []): ?><tr><td colspan="4" class="text-center text-muted">Sem marcas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalMarca" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= url('admin/marcas') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="acao" value="guardar">
            <input type="hidden" name="id" id="marca-id">
            <div class="modal-header">
                <h5 class="modal-title" id="marca-titulo">Nova marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" id="marca-nome" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function prepararMarca(b = null) {
        document.getElementById('marca-id').value   = b ? b.id_brand : '';
        document.getElementById('marca-nome').value = b ? b.nome : '';
        document.getElementById('marca-titulo').textContent = b ? 'Editar marca' : 'Nova marca';
    }
</script>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
