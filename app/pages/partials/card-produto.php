<?php

/**
 * card-produto.php — Cartão de produto (reutilizado na home e na loja).
 *
 * Espera:
 *   $produto  array com id_product, nome, slug, preco, preco_promo,
 *             descricao_curta e (opcional) 'imagem'
 */

$imagemProduto = $produto['imagem'] ?? null;
$temPromo = ! empty($produto['preco_promo']) && (float) $produto['preco_promo'] < (float) $produto['preco'];
$naWishlist = wishlist_tem((int) $produto['id_product']);
?>
<div class="card h-100 border-0 shadow-sm card-produto">
    <div class="position-relative overflow-hidden" style="aspect-ratio: 4/3;">
        <a href="<?= url('shop/' . $produto['slug']) ?>">
            <img src="<?= e(imagem_url($imagemProduto)) ?>" alt="<?= e($produto['nome']) ?>"
                 class="w-100 h-100 object-fit-cover" loading="lazy">
        </a>

        <?php if ($temPromo): ?>
            <span class="badge bg-primary position-absolute top-0 start-0 m-2">Promoção</span>
        <?php endif; ?>

        <!-- Botão wishlist -->
        <button type="button"
                class="btn btn-light btn-sm rounded-circle position-absolute top-0 end-0 m-2 btn-wishlist <?= $naWishlist ? 'ativo' : '' ?>"
                data-produto="<?= (int) $produto['id_product'] ?>" title="Adicionar aos favoritos">
            <i class="bi <?= $naWishlist ? 'bi-heart-fill text-primary' : 'bi-heart' ?>"></i>
        </button>
    </div>

    <div class="card-body d-flex flex-column">
        <h3 class="h6 fw-bold mb-1">
            <a href="<?= url('shop/' . $produto['slug']) ?>" class="text-decoration-none text-dark stretched-link-none">
                <?= e($produto['nome']) ?>
            </a>
        </h3>
        <p class="text-muted small mb-3 flex-grow-1"><?= e($produto['descricao_curta'] ?? '') ?></p>

        <div class="d-flex align-items-center justify-content-between">
            <div>
                <?php if ($temPromo): ?>
                    <span class="text-decoration-line-through text-muted small"><?= e(moeda($produto['preco'])) ?></span><br>
                    <span class="fw-bold text-primary"><?= e(moeda($produto['preco_promo'])) ?></span>
                <?php else: ?>
                    <span class="fw-bold text-primary"><?= e(moeda($produto['preco'])) ?></span>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-sm btn-primary btn-add-carrinho"
                    data-produto="<?= (int) $produto['id_product'] ?>" title="Adicionar ao carrinho">
                <i class="bi bi-bag-plus"></i>
            </button>
        </div>
    </div>
</div>
