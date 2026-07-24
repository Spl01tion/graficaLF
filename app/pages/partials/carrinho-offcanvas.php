<?php

/**
 * carrinho-offcanvas.php — Conteúdo do painel lateral do carrinho.
 * Renderizado no header (estado inicial) e devolvido pelo endpoint AJAX
 * após cada alteração, para o painel reflectir sempre o estado actual.
 */

$itens = carrinho_itens();
?>
<div class="offcanvas-body d-flex flex-column" id="corpo-carrinho">
    <?php if ($itens === []): ?>
        <div class="text-center my-auto text-muted">
            <i class="bi bi-bag-x fs-1 d-block mb-2"></i>
            O seu carrinho está vazio.
        </div>
        <a href="<?= url('shop') ?>" class="btn btn-primary w-100 mt-3">Ver produtos</a>
    <?php else: ?>
        <ul class="list-unstyled mb-3">
            <?php foreach ($itens as $item): ?>
                <li class="d-flex gap-2 mb-3 pb-3 border-bottom">
                    <img src="<?= e(imagem_url($item['imagem'])) ?>" alt="<?= e($item['nome']) ?>"
                         class="rounded object-fit-cover" style="width:56px;height:56px;">
                    <div class="flex-grow-1 small">
                        <div class="fw-semibold"><?= e($item['nome']) ?></div>
                        <?php if ($item['opcoes'] !== ''): ?>
                            <div class="text-muted" style="font-size:.75rem;"><?= e($item['opcoes']) ?></div>
                        <?php endif; ?>
                        <div class="text-muted"><?= (int) $item['quantidade'] ?> × <?= e(moeda($item['preco_unit'])) ?></div>
                    </div>
                    <button class="btn btn-sm text-danger p-0 btn-remover-item" data-chave="<?= e($item['chave']) ?>" title="Remover">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="mt-auto">
            <div class="d-flex justify-content-between fw-bold mb-3">
                <span>Subtotal</span>
                <span><?= e(moeda(carrinho_subtotal())) ?></span>
            </div>
            <a href="<?= url('carrinho') ?>" class="btn btn-outline-dark w-100 mb-2">Ver carrinho</a>
            <a href="<?= url('fazer-pedido') ?>" class="btn btn-primary w-100">Fazer pedido</a>
        </div>
    <?php endif; ?>
</div>
