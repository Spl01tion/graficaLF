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
//  LISTA (pesquisa + filtros + paginação)
// ==================================================================
$q          = trim((string) ($_GET['q'] ?? ''));
$fCategoria = (int) ($_GET['categoria'] ?? 0);
$fEstado    = (string) ($_GET['estado'] ?? '');
$porPagina  = 20;

// Filtros — sempre com marcadores, nunca concatenados no SQL.
$where  = ['1 = 1'];
$params = [];

if ($q !== '') {
    // Com prepares nativos (emulação desligada) cada marcador só pode
    // aparecer uma vez — daí :q1/:q2/:q3 com o mesmo valor.
    $where[]      = '(p.nome LIKE :q1 OR p.sku LIKE :q2 OR p.slug LIKE :q3)';
    $params['q1'] = $params['q2'] = $params['q3'] = '%' . $q . '%';
}
if ($fCategoria > 0) {
    $where[]       = 'p.id_category = :cat';
    $params['cat'] = $fCategoria;
}
if ($fEstado === '1' || $fEstado === '0') {
    $where[]         = 'p.ativo = :ativo';
    $params['ativo'] = (int) $fEstado;
}
$whereSql = implode(' AND ', $where);

$total        = (int) (query_row("SELECT COUNT(*) AS t FROM products p WHERE {$whereSql}", $params)['t'] ?? 0);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$pagina       = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPaginas);
$offset       = ($pagina - 1) * $porPagina;

$produtos = query(
    "SELECT p.*, c.nome AS categoria_nome,
            (SELECT image FROM product_images i WHERE i.id_product=p.id_product ORDER BY principal DESC, ordem LIMIT 1) AS imagem
     FROM products p LEFT JOIN categories c ON c.id_category=p.id_category
     WHERE {$whereSql}
     ORDER BY p.created_at DESC, p.id_product DESC
     LIMIT {$porPagina} OFFSET {$offset}",
    $params
) ?: [];

$categorias = query('SELECT id_category, nome FROM categories ORDER BY nome') ?: [];
$filtros    = ['q' => $q, 'categoria' => $fCategoria ?: '', 'estado' => $fEstado];

$tabela = parcial('admin-produtos-tabela', compact('produtos', 'total', 'pagina', 'totalPaginas', 'filtros'));

// Pesquisa dinâmica: devolve só a tabela, sem o resto do painel.
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
    header('Content-Type: text/html; charset=utf-8');
    echo $tabela;
    return;
}

$tituloPainel = 'Produtos';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <h2 class="h6 fw-bold mb-0 text-nowrap">Lista de produtos</h2>
    <a href="<?= url('admin/produtos?form=novo') ?>" class="btn btn-primary btn-sm text-nowrap"><i class="bi bi-plus-lg me-1"></i>Novo produto</a>
</div>

<!-- Pesquisa dinâmica. Sem JavaScript continua a funcionar como formulário GET. -->
<form method="get" action="<?= url('admin/produtos') ?>" id="filtros-produtos" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" name="q" id="busca-produtos" class="form-control border-start-0 ps-0"
                           value="<?= e($q) ?>" placeholder="Pesquisar por nome, SKU ou slug..."
                           autocomplete="off" aria-label="Pesquisar produtos">
                    <span class="input-group-text bg-white d-none" id="busca-spinner">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    </span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="categoria" class="form-select" aria-label="Filtrar por categoria">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id_category'] ?>" <?= $fCategoria === (int) $c['id_category'] ? 'selected' : '' ?>>
                            <?= e($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="estado" class="form-select" aria-label="Filtrar por estado">
                    <option value="">Todos os estados</option>
                    <option value="1" <?= $fEstado === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $fEstado === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
        </div>
        <noscript><button class="btn btn-primary btn-sm mt-2">Filtrar</button></noscript>
    </div>
</form>

<div id="lista-produtos"><?= $tabela ?></div>

<script>
    // ---- Pesquisa dinâmica da lista de produtos ----
    (() => {
        const form    = document.getElementById('filtros-produtos');
        const lista   = document.getElementById('lista-produtos');
        const busca   = document.getElementById('busca-produtos');
        const spinner = document.getElementById('busca-spinner');
        if (!form || !lista) return;

        const BASE = form.action;
        let temporizador = null;
        let pedido = null;          // AbortController do pedido em curso

        // Carrega uma lista de resultados e substitui a tabela.
        // historico: 'replace' ao filtrar (não enche o histórico com cada tecla),
        // 'push' ao paginar (o botão "anterior" do browser volta à página certa).
        async function carregar(url, { historico = 'replace' } = {}) {
            pedido?.abort();        // um resultado antigo nunca pode chegar depois do novo
            pedido = new AbortController();

            spinner.classList.remove('d-none');
            lista.style.opacity = '.5';

            try {
                const r = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: pedido.signal,
                });
                if (!r.ok) throw new Error(r.status);

                lista.innerHTML = await r.text();
                if (historico === 'push')    history.pushState(null, '', url);
                if (historico === 'replace') history.replaceState(null, '', url);
            } catch (e) {
                if (e.name !== 'AbortError') {
                    lista.innerHTML = '<div class="alert alert-danger mb-0">'
                        + 'Não foi possível carregar os produtos. Actualize a página.</div>';
                }
            } finally {
                spinner.classList.add('d-none');
                lista.style.opacity = '';
            }
        }

        // URL com os filtros actuais (a página volta sempre à primeira).
        function urlFiltros() {
            const dados = new URLSearchParams(new FormData(form));
            for (const [k, v] of [...dados]) {
                if (v === '') dados.delete(k);
            }
            const qs = dados.toString();
            return BASE + (qs ? '?' + qs : '');
        }

        // Escrever espera 300 ms; mudar um select aplica logo.
        busca.addEventListener('input', () => {
            clearTimeout(temporizador);
            temporizador = setTimeout(() => carregar(urlFiltros()), 300);
        });
        form.querySelectorAll('select').forEach((s) => {
            s.addEventListener('change', () => carregar(urlFiltros()));
        });

        // Sem JavaScript o formulário faz GET normal; com ele, não recarrega a página.
        form.addEventListener('submit', (ev) => {
            ev.preventDefault();
            clearTimeout(temporizador);
            carregar(urlFiltros());
        });

        // Esc limpa a pesquisa.
        busca.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape' && busca.value !== '') {
                busca.value = '';
                carregar(urlFiltros());
            }
        });

        // A paginação vem dentro do HTML substituído — daí a delegação.
        lista.addEventListener('click', (ev) => {
            const link = ev.target.closest('.pagination a.page-link');
            if (!link || link.closest('.disabled')) return;
            ev.preventDefault();
            carregar(link.href, { historico: 'push' });
            lista.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Botões "anterior/seguinte" do browser.
        window.addEventListener('popstate', () => carregar(location.href, { historico: false }));
    })();
</script>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
