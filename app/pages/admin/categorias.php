<?php

/**
 * admin/categorias.php — CRUD de categorias (com slug automático).
 */

// ---- Controlador (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'guardar') {
        $id    = (int) ($_POST['id'] ?? 0);
        $nome  = trim($_POST['nome'] ?? '');
        $desc  = trim($_POST['descricao'] ?? '');

        if ($nome === '') {
            flash('erro', 'O nome é obrigatório.');
        } else {
            $slug = str_to_url($nome);
            // Garante slug único (ignora a própria linha em edição).
            $existe = query_row('SELECT id_category FROM categories WHERE slug = :s AND id_category <> :id', ['s' => $slug, 'id' => $id]);
            if ($existe) {
                $slug .= '-' . substr(md5((string) microtime()), 0, 4);
            }

            if ($id > 0) {
                execute('UPDATE categories SET nome=:n, slug=:s, descricao=:d WHERE id_category=:id',
                    ['n' => $nome, 's' => $slug, 'd' => $desc ?: null, 'id' => $id]);
                flash('sucesso', 'Categoria actualizada.');
            } else {
                inserir('categories', ['nome' => $nome, 'slug' => $slug, 'descricao' => $desc ?: null]);
                flash('sucesso', 'Categoria criada.');
            }
        }
    } elseif ($acao === 'apagar') {
        execute('DELETE FROM categories WHERE id_category = :id', ['id' => (int) $_POST['id']]);
        flash('sucesso', 'Categoria apagada.');
    }

    redirect('admin/categorias');
}

$categorias = query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.id_category=c.id_category) AS n FROM categories c ORDER BY c.nome') ?: [];

$tituloPainel = 'Categorias';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($categorias) ?> categoria(s)</p>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCategoria"
            onclick="prepararCategoria()"><i class="bi bi-plus-lg me-1"></i>Nova categoria</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Nome</th><th>Slug</th><th>Produtos</th><th class="text-end">Acções</th></tr></thead>
            <tbody>
                <?php foreach ($categorias as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($c['nome']) ?></td>
                        <td class="small text-muted"><?= e($c['slug']) ?></td>
                        <td><span class="badge bg-secondary"><?= (int) $c['n'] ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"
                                onclick='prepararCategoria(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                data-bs-toggle="modal" data-bs-target="#modalCategoria"><i class="bi bi-pencil"></i></button>
                            <form method="post" action="<?= url('admin/categorias') ?>" class="d-inline"
                                  onsubmit="return confirm('Apagar esta categoria?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="apagar">
                                <input type="hidden" name="id" value="<?= (int) $c['id_category'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categorias === []): ?><tr><td colspan="4" class="text-center text-muted">Sem categorias.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal criar/editar -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= url('admin/categorias') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="acao" value="guardar">
            <input type="hidden" name="id" id="cat-id">
            <div class="modal-header">
                <h5 class="modal-title" id="cat-titulo">Nova categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" id="cat-nome" class="form-control" required>
                    <div class="form-text">O slug (URL) é gerado automaticamente.</div>
                </div>
                <div class="mb-0">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" id="cat-desc" rows="2" class="form-control"></textarea>
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
    function prepararCategoria(c = null) {
        document.getElementById('cat-id').value   = c ? c.id_category : '';
        document.getElementById('cat-nome').value = c ? c.nome : '';
        document.getElementById('cat-desc').value = c ? (c.descricao || '') : '';
        document.getElementById('cat-titulo').textContent = c ? 'Editar categoria' : 'Nova categoria';
    }
</script>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
