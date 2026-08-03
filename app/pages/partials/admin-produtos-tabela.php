<?php

/**
 * admin-produtos-tabela.php — Resultados da lista de produtos do admin.
 *
 * Renderizado nas duas situações: no carregamento normal da página e na
 * resposta AJAX da pesquisa dinâmica (é por isso que vive num parcial).
 *
 * Variáveis esperadas — injectadas por parcial() via extract(); as
 * anotações @var declaram-nas ao analisador do editor, que de outra
 * forma as assinala como indefinidas neste ficheiro.
 */

/** @var array<int,array<string,mixed>> $produtos     Linhas da página actual. */
/** @var int                            $total        Total de resultados (com os filtros aplicados). */
/** @var int                            $pagina       Página actual. */
/** @var int                            $totalPaginas Número total de páginas. */
/** @var array<string,mixed>            $filtros      q / categoria / estado — para os links da paginação. */

$linkPagina = static function (int $n) use ($filtros): string {
    $q = array_filter($filtros, static fn ($v) => $v !== '' && $v !== null);
    $q['page'] = $n;

    return url('admin/produtos') . '?' . http_build_query($q);
};
?>

<div class="d-flex justify-content-between align-items-center mb-2">
    <p class="text-muted small mb-0">
        <?= (int) $total ?> produto(s)
        <?php if (($filtros['q'] ?? '') !== ''): ?>
            para &ldquo;<strong><?= e($filtros['q']) ?></strong>&rdquo;
        <?php endif; ?>
    </p>
    <?php if ($totalPaginas > 1): ?>
        <p class="text-muted small mb-0">Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?></p>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th></th><th>Nome</th><th>Categoria</th><th>Preço</th>
                    <th>Stock</th><th>Estado</th><th class="text-end">Acções</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td style="width:60px;"><img src="<?= e(imagem_url($p['imagem'])) ?>" class="rounded object-fit-cover" style="width:44px;height:44px;" alt=""></td>
                        <td>
                            <div class="fw-semibold"><?= e($p['nome']) ?></div>
                            <div class="d-flex gap-1 align-items-center">
                                <?php if ($p['sku']): ?><span class="text-muted small"><?= e($p['sku']) ?></span><?php endif; ?>
                                <?php if ($p['destaque']): ?><span class="badge bg-warning text-dark">Destaque</span><?php endif; ?>
                            </div>
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
                            <a href="<?= url('shop/' . $p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver na loja"><i class="bi bi-eye"></i></a>
                            <a href="<?= url('admin/produtos?form=' . (int) $p['id_product']) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= url('admin/produtos') ?>" class="d-inline" onsubmit="return confirm('Apagar este produto?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="apagar">
                                <input type="hidden" name="id" value="<?= (int) $p['id_product'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Apagar"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if ($produtos === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-search fs-3 d-block mb-2 opacity-50"></i>
                            <?php if (($filtros['q'] ?? '') !== ''): ?>
                                Nenhum produto encontrado para &ldquo;<strong><?= e($filtros['q']) ?></strong>&rdquo;.
                            <?php else: ?>
                                Sem produtos com estes filtros.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
    <nav class="mt-3" aria-label="Navegação de páginas">
        <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $linkPagina(max(1, $pagina - 1)) ?>" aria-label="Página anterior"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php foreach (paginacao_numeros($pagina, $totalPaginas) as $n): ?>
                <?php if ($n === '…'): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php else: ?>
                    <li class="page-item <?= $n === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $linkPagina($n) ?>" <?= $n === $pagina ? 'aria-current="page"' : '' ?>><?= $n ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $linkPagina(min($totalPaginas, $pagina + 1)) ?>" aria-label="Página seguinte"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
