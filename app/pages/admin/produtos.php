<?php

/**
 * admin/produtos.php — CRUD de produtos.
 *
 *   /admin/produtos            -> lista
 *   /admin/produtos?form=novo  -> formulário de criação
 *   /admin/produtos?form=ID    -> formulário de edição
 *
 * Suporta upload de múltiplas imagens (validado) e gestão dinâmica de
 * opções/variações (grupo + valor + ajuste de preço).
 */

// ==================================================================
//  CONTROLADOR (POST)
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'guardar') {
        $id    = (int) ($_POST['id'] ?? 0);
        $nome  = trim($_POST['nome'] ?? '');
        $preco = (float) ($_POST['preco'] ?? 0);

        $erros = [];
        if ($nome === '') { $erros[] = 'O nome é obrigatório.'; }
        if ($preco <= 0)  { $erros[] = 'O preço deve ser maior que zero.'; }

        if ($erros !== []) {
            flash('erro', implode(' ', $erros));
            redirect('admin/produtos?form=' . ($id ?: 'novo'));
        }

        $slug = str_to_url($nome);
        if (query_row('SELECT id_product FROM products WHERE slug=:s AND id_product<>:id', ['s' => $slug, 'id' => $id])) {
            $slug .= '-' . substr(md5((string) microtime()), 0, 4);
        }

        $dados = [
            'id_category'     => ($_POST['id_category'] ?? '') !== '' ? (int) $_POST['id_category'] : null,
            'id_brand'        => ($_POST['id_brand'] ?? '') !== '' ? (int) $_POST['id_brand'] : null,
            'nome'            => $nome,
            'slug'            => $slug,
            'sku'             => trim($_POST['sku'] ?? '') ?: null,
            'descricao_curta' => trim($_POST['descricao_curta'] ?? '') ?: null,
            'descricao'       => trim($_POST['descricao'] ?? '') ?: null,
            'preco'           => $preco,
            'preco_promo'     => ($_POST['preco_promo'] ?? '') !== '' ? (float) $_POST['preco_promo'] : null,
            'stock'           => (int) ($_POST['stock'] ?? 0),
            'destaque'        => isset($_POST['destaque']) ? 1 : 0,
            'ativo'           => isset($_POST['ativo']) ? 1 : 0,
        ];

        if ($id > 0) {
            execute(
                'UPDATE products SET id_category=:id_category, id_brand=:id_brand, nome=:nome, slug=:slug,
                        sku=:sku, descricao_curta=:descricao_curta, descricao=:descricao, preco=:preco,
                        preco_promo=:preco_promo, stock=:stock, destaque=:destaque, ativo=:ativo
                 WHERE id_product=:id',
                $dados + ['id' => $id]
            );
        } else {
            $id = inserir('products', $dados);
        }

        // --- Opções (apaga e recria a partir dos arrays submetidos) ---
        execute('DELETE FROM product_options WHERE id_product = :id', ['id' => $id]);
        $grupos  = $_POST['opcao_grupo'] ?? [];
        $valores = $_POST['opcao_valor'] ?? [];
        $ajustes = $_POST['opcao_ajuste'] ?? [];
        foreach ($grupos as $i => $g) {
            $g = trim($g);
            $v = trim($valores[$i] ?? '');
            if ($g !== '' && $v !== '') {
                inserir('product_options', [
                    'id_product'   => $id,
                    'grupo'        => $g,
                    'valor'        => $v,
                    'ajuste_preco' => (float) ($ajustes[$i] ?? 0),
                    'ordem'        => $i,
                ]);
            }
        }

        // --- Imagens novas (upload validado) ---
        if (! empty($_FILES['imagens']['name'][0])) {
            $temPrincipal = (bool) query_row('SELECT id_image FROM product_images WHERE id_product=:id AND principal=1', ['id' => $id]);
            foreach ($_FILES['imagens']['name'] as $k => $nomeFich) {
                if ($_FILES['imagens']['error'][$k] !== UPLOAD_ERR_OK) { continue; }
                $res = upload_imagem([
                    'name' => $nomeFich, 'type' => $_FILES['imagens']['type'][$k],
                    'tmp_name' => $_FILES['imagens']['tmp_name'][$k], 'error' => $_FILES['imagens']['error'][$k],
                    'size' => $_FILES['imagens']['size'][$k],
                ], 'prod');
                if ($res['ok']) {
                    inserir('product_images', [
                        'id_product' => $id, 'image' => $res['nome'],
                        'principal' => $temPrincipal ? 0 : 1, 'ordem' => $k,
                    ]);
                    $temPrincipal = true;
                } else {
                    flash('erro', $res['erro']);
                }
            }
        }

        flash('sucesso', 'Produto guardado.');
        redirect('admin/produtos');
    }

    if ($acao === 'apagar') {
        $id = (int) $_POST['id'];
        foreach (query('SELECT image FROM product_images WHERE id_product=:id', ['id' => $id]) ?: [] as $im) {
            apagar_upload($im['image']);
        }
        execute('DELETE FROM products WHERE id_product = :id', ['id' => $id]); // ON DELETE CASCADE trata imagens/opções
        flash('sucesso', 'Produto apagado.');
        redirect('admin/produtos');
    }

    if ($acao === 'apagar_imagem') {
        $img = query_row('SELECT * FROM product_images WHERE id_image=:i', ['i' => (int) $_POST['id_image']]);
        if ($img) {
            apagar_upload($img['image']);
            execute('DELETE FROM product_images WHERE id_image=:i', ['i' => $img['id_image']]);
        }
        redirect('admin/produtos?form=' . (int) $_POST['id']);
    }
}

// ==================================================================
//  FORMULÁRIO (criar/editar)
// ==================================================================
$form = $_GET['form'] ?? null;
if ($form !== null) {
    $editar = $form !== 'novo';
    $p = $editar
        ? query_row('SELECT * FROM products WHERE id_product = :id', ['id' => (int) $form])
        : null;

    if ($editar && ! $p) { redirect('admin/produtos'); }

    $categorias = query('SELECT * FROM categories ORDER BY nome') ?: [];
    $marcas     = query('SELECT * FROM brands ORDER BY nome') ?: [];
    $imagens    = $editar ? (query('SELECT * FROM product_images WHERE id_product=:id ORDER BY principal DESC, ordem', ['id' => $p['id_product']]) ?: []) : [];
    $opcoes     = $editar ? (query('SELECT * FROM product_options WHERE id_product=:id ORDER BY ordem', ['id' => $p['id_product']]) ?: []) : [];

    $val = static fn (string $c, $d = '') => e($p[$c] ?? $d);

    $tituloPainel = $editar ? 'Editar produto' : 'Novo produto';
    require __DIR__ . '/../partials/admin_header.php';
    ?>
    <a href="<?= url('admin/produtos') ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Voltar à lista</a>

    <form method="post" action="<?= url('admin/produtos') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="guardar">
        <input type="hidden" name="id" value="<?= $editar ? (int) $p['id_product'] : '' ?>">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Informação</h2>
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="nome" class="form-control" value="<?= $val('nome') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição curta</label>
                            <input type="text" name="descricao_curta" class="form-control" value="<?= $val('descricao_curta') ?>" maxlength="255">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Descrição completa</label>
                            <textarea name="descricao" rows="5" class="form-control"><?= $val('descricao') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Opções / variações -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h6 fw-bold mb-0">Opções / variações</h2>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOpcao()"><i class="bi bi-plus"></i> Adicionar</button>
                        </div>
                        <div class="form-text mb-2">Ex.: grupo "Tamanho", valor "M", ajuste de preço "+50".</div>
                        <div id="opcoes-lista">
                            <?php foreach ($opcoes as $o): ?>
                                <div class="row g-2 mb-2 linha-opcao">
                                    <div class="col-4"><input type="text" name="opcao_grupo[]" class="form-control form-control-sm" placeholder="Grupo" value="<?= e($o['grupo']) ?>"></div>
                                    <div class="col-4"><input type="text" name="opcao_valor[]" class="form-control form-control-sm" placeholder="Valor" value="<?= e($o['valor']) ?>"></div>
                                    <div class="col-3"><input type="number" step="0.01" name="opcao_ajuste[]" class="form-control form-control-sm" placeholder="Ajuste" value="<?= e($o['ajuste_preco']) ?>"></div>
                                    <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.linha-opcao').remove()"><i class="bi bi-x"></i></button></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Imagens -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Imagens</h2>
                        <?php if ($imagens !== []): ?>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach ($imagens as $im): ?>
                                    <div class="position-relative">
                                        <img src="<?= e(imagem_url($im['image'])) ?>" class="rounded border object-fit-cover" style="width:90px;height:90px;">
                                        <?php if ($im['principal']): ?><span class="badge bg-primary position-absolute bottom-0 start-0 m-1">Capa</span><?php endif; ?>
                                        <button type="submit" name="acao" value="apagar_imagem" form="form-apagar-img-<?= (int) $im['id_image'] ?>"
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle p-0" style="width:22px;height:22px;line-height:1;">×</button>
                                    </div>
                                    <form id="form-apagar-img-<?= (int) $im['id_image'] ?>" method="post" action="<?= url('admin/produtos') ?>" class="d-none">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $p['id_product'] ?>">
                                        <input type="hidden" name="id_image" value="<?= (int) $im['id_image'] ?>">
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagens[]" class="form-control" accept="image/*" multiple>
                        <div class="form-text">JPG, PNG, WEBP ou GIF — máx. 3 MB cada. A primeira imagem torna-se a capa.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Preço e stock</h2>
                        <div class="mb-3">
                            <label class="form-label">Preço (MZN) *</label>
                            <input type="number" step="0.01" name="preco" class="form-control" value="<?= $val('preco') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preço promocional</label>
                            <input type="number" step="0.01" name="preco_promo" class="form-control" value="<?= $val('preco_promo') ?>">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" value="<?= $editar ? (int) $p['stock'] : 0 ?>">
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Organização</h2>
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="id_category" class="form-select">
                                <option value="">— Nenhuma —</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int) $c['id_category'] ?>" <?= ($editar && $p['id_category'] == $c['id_category']) ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Marca</label>
                            <select name="id_brand" class="form-select">
                                <option value="">— Nenhuma —</option>
                                <?php foreach ($marcas as $b): ?>
                                    <option value="<?= (int) $b['id_brand'] ?>" <?= ($editar && $p['id_brand'] == $b['id_brand']) ? 'selected' : '' ?>><?= e($b['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" value="<?= $val('sku') ?>">
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" name="destaque" id="destaque" <?= (! $editar || $p['destaque']) ? ($editar && $p['destaque'] ? 'checked' : '') : '' ?>>
                            <label class="form-check-label" for="destaque">Produto em destaque</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="ativo" id="ativo" <?= (! $editar || $p['ativo']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Activo (visível na loja)</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="bi bi-check-lg me-1"></i>Guardar produto</button>
            </div>
        </div>
    </form>

    <script>
        function addOpcao() {
            const html = `<div class="row g-2 mb-2 linha-opcao">
                <div class="col-4"><input type="text" name="opcao_grupo[]" class="form-control form-control-sm" placeholder="Grupo"></div>
                <div class="col-4"><input type="text" name="opcao_valor[]" class="form-control form-control-sm" placeholder="Valor"></div>
                <div class="col-3"><input type="number" step="0.01" name="opcao_ajuste[]" class="form-control form-control-sm" placeholder="Ajuste" value="0"></div>
                <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.linha-opcao').remove()"><i class="bi bi-x"></i></button></div>
            </div>`;
            document.getElementById('opcoes-lista').insertAdjacentHTML('beforeend', html);
        }
    </script>
    <?php
    require __DIR__ . '/../partials/admin_footer.php';
    return;
}

// ==================================================================
//  LISTA
// ==================================================================
$produtos = query(
    'SELECT p.*, c.nome AS categoria_nome,
            (SELECT image FROM product_images i WHERE i.id_product=p.id_product ORDER BY principal DESC, ordem LIMIT 1) AS imagem
     FROM products p LEFT JOIN categories c ON c.id_category=p.id_category
     ORDER BY p.created_at DESC'
) ?: [];

$tituloPainel = 'Produtos';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($produtos) ?> produto(s)</p>
    <a href="<?= url('admin/produtos?form=novo') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo produto</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th></th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Stock</th><th>Estado</th><th class="text-end">Acções</th></tr></thead>
            <tbody>
                <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td style="width:60px;"><img src="<?= e(imagem_url($p['imagem'])) ?>" class="rounded object-fit-cover" style="width:44px;height:44px;"></td>
                        <td>
                            <div class="fw-semibold"><?= e($p['nome']) ?></div>
                            <?php if ($p['destaque']): ?><span class="badge bg-warning text-dark">Destaque</span><?php endif; ?>
                        </td>
                        <td class="small"><?= e($p['categoria_nome'] ?? '—') ?></td>
                        <td>
                            <?php if ($p['preco_promo']): ?>
                                <span class="text-decoration-line-through text-muted small"><?= e(moeda($p['preco'])) ?></span>
                                <span class="text-primary fw-semibold"><?= e(moeda($p['preco_promo'])) ?></span>
                            <?php else: ?><?= e(moeda($p['preco'])) ?><?php endif; ?>
                        </td>
                        <td><?= (int) $p['stock'] ?></td>
                        <td><span class="badge bg-<?= $p['ativo'] ? 'success' : 'secondary' ?>"><?= $p['ativo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= url('shop/' . $p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver"><i class="bi bi-eye"></i></a>
                            <a href="<?= url('admin/produtos?form=' . (int) $p['id_product']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= url('admin/produtos') ?>" class="d-inline" onsubmit="return confirm('Apagar este produto?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="apagar">
                                <input type="hidden" name="id" value="<?= (int) $p['id_product'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($produtos === []): ?><tr><td colspan="7" class="text-center text-muted">Sem produtos.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
