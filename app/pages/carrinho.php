<?php

/**
 * carrinho.php — Página do carrinho + endpoint de acções (AJAX/form).
 *
 * Acções (POST, com CSRF):
 *   ?acao=adicionar   id_produto, quantidade, opcoes[]
 *   ?acao=actualizar  chave, quantidade
 *   ?acao=remover     chave
 *   ?acao=limpar
 *   ?acao=cupao       codigo   (aplica/remove cupão)
 *
 * Pedidos AJAX (fetch) recebem JSON; submissões normais são redireccionadas.
 */

$acao = $_GET['acao'] ?? null;
$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao) {
    csrf_verificar();

    switch ($acao) {
        case 'adicionar':
            $ok = carrinho_adicionar(
                (int) ($_POST['id_produto'] ?? 0),
                (int) ($_POST['quantidade'] ?? 1),
                array_map('intval', (array) ($_POST['opcoes'] ?? []))
            );
            $mensagem = $ok ? 'Produto adicionado ao carrinho.' : 'Não foi possível adicionar o produto.';
            break;

        case 'actualizar':
            carrinho_actualizar((string) ($_POST['chave'] ?? ''), (int) ($_POST['quantidade'] ?? 0));
            $mensagem = 'Carrinho actualizado.';
            break;

        case 'remover':
            carrinho_remover((string) ($_POST['chave'] ?? ''));
            $mensagem = 'Item removido.';
            break;

        case 'limpar':
            carrinho_limpar();
            $mensagem = 'Carrinho esvaziado.';
            break;

        case 'cupao':
            $codigo = trim($_POST['codigo'] ?? '');
            if ($codigo === '') {
                unset($_SESSION['cupao']);
                $mensagem = 'Cupão removido.';
            } else {
                $res = cupao_validar($codigo, carrinho_subtotal());
                if ($res['ok']) {
                    $_SESSION['cupao'] = ['codigo' => strtoupper($codigo)];
                    $mensagem = $res['msg'];
                } else {
                    unset($_SESSION['cupao']);
                    if ($ajax) {
                        json_saida(['ok' => false, 'mensagem' => $res['msg'], 'contador' => carrinho_qtd()]);
                    }
                    flash('erro', $res['msg']);
                    redirect('carrinho');
                }
            }
            break;

        default:
            $mensagem = '';
    }

    if ($ajax) {
        json_saida([
            'ok'         => true,
            'mensagem'   => $mensagem,
            'contador'   => carrinho_qtd(),
            'offcanvas'  => parcial('carrinho-offcanvas'),
        ]);
    }

    flash('sucesso', $mensagem);
    redirect('carrinho');
}

/** Resposta JSON curta para os pedidos AJAX. */
function json_saida(array $dados): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------------
//  Vista da página do carrinho
// ------------------------------------------------------------------
$itens     = carrinho_itens();
$subtotal  = carrinho_subtotal();
$desconto  = carrinho_desconto();
$total     = carrinho_total();
$cupao     = $_SESSION['cupao']['codigo'] ?? '';

$titulo    = 'Carrinho';
$descricao = 'Os produtos no seu carrinho.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4"><i class="bi bi-bag me-2"></i>O seu carrinho</h1>

        <?php if ($itens === []): ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x display-4 text-muted"></i>
                <p class="text-muted mt-3">O seu carrinho está vazio.</p>
                <a href="<?= url('shop') ?>" class="btn btn-primary">Ver produtos</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">Produto</th>
                                        <th class="text-center">Quantidade</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens as $item): ?>
                                        <tr>
                                            <td style="width:80px;">
                                                <img src="<?= e(imagem_url($item['imagem'])) ?>" alt=""
                                                     class="rounded object-fit-cover" style="width:64px;height:64px;">
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= e($item['nome']) ?></div>
                                                <?php if ($item['opcoes'] !== ''): ?>
                                                    <div class="text-muted small"><?= e($item['opcoes']) ?></div>
                                                <?php endif; ?>
                                                <div class="text-muted small"><?= e(moeda($item['preco_unit'])) ?> / un.</div>
                                            </td>
                                            <td class="text-center" style="width:140px;">
                                                <form method="post" action="<?= url('carrinho?acao=actualizar') ?>" class="d-inline-flex form-qtd">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="chave" value="<?= e($item['chave']) ?>">
                                                    <input type="number" name="quantidade" value="<?= (int) $item['quantidade'] ?>"
                                                           min="0" max="9999" class="form-control form-control-sm text-center"
                                                           style="width:80px;" onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="text-end fw-semibold"><?= e(moeda($item['subtotal'])) ?></td>
                                            <td class="text-end">
                                                <form method="post" action="<?= url('carrinho?acao=remover') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="chave" value="<?= e($item['chave']) ?>">
                                                    <button class="btn btn-sm text-danger" title="Remover"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <a href="<?= url('shop') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Continuar a comprar</a>
                        <form method="post" action="<?= url('carrinho?acao=limpar') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Esvaziar</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Resumo</h2>

                            <form method="post" action="<?= url('carrinho?acao=cupao') ?>" class="mb-3">
                                <?= csrf_field() ?>
                                <label class="form-label small">Cupão de desconto</label>
                                <div class="input-group">
                                    <input type="text" name="codigo" class="form-control" placeholder="Ex.: BEMVINDO10"
                                           value="<?= e($cupao) ?>">
                                    <button class="btn btn-outline-primary" type="submit">Aplicar</button>
                                </div>
                            </form>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span><span><?= e(moeda($subtotal)) ?></span>
                            </div>
                            <?php if ($desconto > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Desconto (<?= e($cupao) ?>)</span><span>-<?= e(moeda($desconto)) ?></span>
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                                <span>Total</span><span class="text-primary"><?= e(moeda($total)) ?></span>
                            </div>
                            <a href="<?= url('fazer-pedido') ?>" class="btn btn-primary w-100 fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i>Fazer pedido
                            </a>
                            <p class="text-muted small text-center mt-2 mb-0">
                                Sem pagamento online — combinamos consigo por email/WhatsApp.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
