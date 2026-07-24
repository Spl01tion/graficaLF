<?php

/**
 * fazer-pedido.php — Finalização do pedido (sem pagamento online).
 *
 * Funciona para clientes autenticados E convidados (guest).
 *
 * Ao submeter:
 *   1. valida CSRF + rate limiting (anti-spam de encomendas)
 *   2. valida os dados de contacto/entrega
 *   3. numa TRANSACÇÃO: cria o pedido, os itens e o 1.º estado do histórico
 *   4. incrementa o uso do cupão (se aplicado)
 *   5. envia email ao cliente (confirmação) e à Gráfica Lifei (notificação)
 *   6. limpa o carrinho e mostra a página de confirmação
 */

// Sem itens no carrinho não há pedido a fazer.
if (carrinho_itens() === []) {
    flash('erro', 'O seu carrinho está vazio.');
    redirect('shop');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verificar();

    if (! rate_limit('pedido', RATE_LIMIT_ORDER, RATE_LIMIT_WINDOW)) {
        $espera = ceil(rate_limit_espera('pedido', RATE_LIMIT_WINDOW) / 60);
        flash('erro', "Demasiados pedidos seguidos. Aguarde {$espera} minuto(s).");
        redirect('fazer-pedido');
    }

    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $entrega  = ($_POST['tipo_entrega'] ?? 'levantamento') === 'entrega' ? 'entrega' : 'levantamento';
    $endereco = trim($_POST['endereco'] ?? '');
    $obs      = trim($_POST['observacoes'] ?? '');

    if ($nome === '' || mb_strlen($nome) < 3) {
        $errors['nome'] = 'Indique o seu nome completo.';
    }
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido.';
    }
    if ($telefone === '' || ! preg_match('/^[0-9+\s()-]{6,30}$/', $telefone)) {
        $errors['telefone'] = 'Indique um telefone/WhatsApp válido.';
    }
    if ($entrega === 'entrega' && $endereco === '') {
        $errors['endereco'] = 'Indique o endereço de entrega.';
    }

    if ($errors === []) {
        $itens    = carrinho_itens();
        $subtotal = carrinho_subtotal();
        $desconto = carrinho_desconto();
        $total    = carrinho_total();

        // Revalida o cupão no momento de gravar (pode ter expirado entretanto).
        $cupaoRow = null;
        if (! empty($_SESSION['cupao']['codigo'])) {
            $res = cupao_validar($_SESSION['cupao']['codigo'], $subtotal);
            if ($res['ok']) {
                $cupaoRow = $res['coupon'];
            }
        }

        try {
            $idOrder = transaccao_pedido($nome, $email, $telefone, $entrega, $endereco, $obs, $itens, $subtotal, $desconto, $total, $cupaoRow);
        } catch (Throwable $ex) {
            registar_log('Erro ao criar pedido: ' . $ex->getMessage());
            flash('erro', 'Ocorreu um erro ao registar o pedido. Tente novamente.');
            redirect('fazer-pedido');
        }

        $referencia = pedido_referencia($idOrder);

        // Enviar emails (confirmação ao cliente + notificação à loja).
        enviar_emails_pedido($idOrder, $referencia, $nome, $email, $telefone, $entrega, $endereco, $obs, $itens, $subtotal, $desconto, $total);

        // Limpar carrinho e guardar referência para a página de sucesso.
        carrinho_limpar();
        $_SESSION['ultimo_pedido'] = $referencia;

        redirect('pedido-recebido');
    }

    guardar_erros($errors, compact('nome', 'email', 'telefone', 'endereco', 'obs'));
    redirect('fazer-pedido');
}

/**
 * Cria o pedido, os itens e o histórico numa única transacção.
 * Se algo falhar, faz-se rollback e nada fica gravado.
 */
function transaccao_pedido(string $nome, string $email, string $telefone, string $entrega, string $endereco, string $obs, array $itens, float $subtotal, float $desconto, float $total, ?array $cupao): int
{
    $pdo = conexao();
    $pdo->beginTransaction();

    try {
        // Referência provisória (actualizada após ter o id).
        $pdo->prepare(
            'INSERT INTO orders (referencia, id_user, cliente_nome, cliente_email, cliente_telefone,
                tipo_entrega, endereco, observacoes, id_coupon, coupon_codigo, subtotal, desconto, total, status)
             VALUES (:ref, :uid, :nome, :email, :tel, :entrega, :endereco, :obs, :cid, :ccod, :sub, :desc, :tot, "pendente")'
        )->execute([
            'ref'      => 'TEMP',
            'uid'      => logado() ? user('id_user') : null,
            'nome'     => $nome,
            'email'    => $email,
            'tel'      => $telefone,
            'entrega'  => $entrega,
            'endereco' => $entrega === 'entrega' ? $endereco : null,
            'obs'      => $obs !== '' ? $obs : null,
            'cid'      => $cupao['id_coupon'] ?? null,
            'ccod'     => $cupao['codigo'] ?? null,
            'sub'      => $subtotal,
            'desc'     => $desconto,
            'tot'      => $total,
        ]);

        $idOrder = (int) $pdo->lastInsertId();
        $referencia = pedido_referencia($idOrder);

        // Actualiza a referência definitiva.
        $pdo->prepare('UPDATE orders SET referencia = :ref WHERE id_order = :id')
            ->execute(['ref' => $referencia, 'id' => $idOrder]);

        // Itens do pedido (com "fotografia" do nome/preço/opções).
        $stmItem = $pdo->prepare(
            'INSERT INTO order_items (id_order, id_product, produto_nome, opcoes, preco_unit, quantidade, subtotal)
             VALUES (:oid, :pid, :nome, :opcoes, :preco, :qtd, :sub)'
        );
        foreach ($itens as $item) {
            $stmItem->execute([
                'oid'    => $idOrder,
                'pid'    => $item['id_product'],
                'nome'   => $item['nome'],
                'opcoes' => $item['opcoes'] !== '' ? $item['opcoes'] : null,
                'preco'  => $item['preco_unit'],
                'qtd'    => $item['quantidade'],
                'sub'    => $item['subtotal'],
            ]);

            // Actualiza contagem de vendas do produto.
            $pdo->prepare('UPDATE products SET vendas = vendas + :q WHERE id_product = :id')
                ->execute(['q' => $item['quantidade'], 'id' => $item['id_product']]);
        }

        // Primeiro estado no histórico.
        $pdo->prepare('INSERT INTO order_status_history (id_order, status, nota) VALUES (:oid, "pendente", "Pedido recebido")')
            ->execute(['oid' => $idOrder]);

        // Incrementa o uso do cupão.
        if ($cupao) {
            $pdo->prepare('UPDATE coupons SET usado = usado + 1 WHERE id_coupon = :id')
                ->execute(['id' => $cupao['id_coupon']]);
        }

        $pdo->commit();

        return $idOrder;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** Monta e envia os dois emails do pedido (cliente + loja). */
function enviar_emails_pedido(int $idOrder, string $ref, string $nome, string $email, string $telefone, string $entrega, string $endereco, string $obs, array $itens, float $subtotal, float $desconto, float $total): void
{
    // Tabela de itens (partilhada pelos dois emails).
    $linhas = '';
    foreach ($itens as $it) {
        $linhas .= '<tr>
            <td style="padding:8px;border-bottom:1px solid #eee;">' . e($it['nome'])
            . ($it['opcoes'] !== '' ? '<br><small style="color:#888;">' . e($it['opcoes']) . '</small>' : '') . '</td>
            <td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' . (int) $it['quantidade'] . '</td>
            <td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">' . e(moeda($it['subtotal'])) . '</td>
        </tr>';
    }

    $entregaTxt = $entrega === 'entrega' ? 'Entrega em: ' . e($endereco) : 'Levantamento na loja';
    $resumo = '
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <thead><tr style="background:#f4f6f8;">
                <th style="padding:8px;text-align:left;">Produto</th>
                <th style="padding:8px;">Qtd</th>
                <th style="padding:8px;text-align:right;">Subtotal</th>
            </tr></thead>
            <tbody>' . $linhas . '</tbody>
        </table>
        <p style="text-align:right;margin:4px 0;">Subtotal: ' . e(moeda($subtotal)) . '</p>'
        . ($desconto > 0 ? '<p style="text-align:right;margin:4px 0;color:#2e7d32;">Desconto: -' . e(moeda($desconto)) . '</p>' : '')
        . '<p style="text-align:right;font-weight:bold;font-size:18px;margin:4px 0;">Total: ' . e(moeda($total)) . '</p>
        <hr>
        <p><strong>Contacto:</strong> ' . e($nome) . ' · ' . e($email) . ' · ' . e($telefone) . '</p>
        <p><strong>Entrega:</strong> ' . $entregaTxt . '</p>'
        . ($obs !== '' ? '<p><strong>Observações:</strong> ' . nl2br(e($obs)) . '</p>' : '');

    // Email ao cliente.
    $corpoCliente = template_email('Pedido recebido — ' . e($ref), '
        <p>Olá ' . e($nome) . ',</p>
        <p>Recebemos o seu pedido com a referência <strong>' . e($ref) . '</strong>.
        A nossa equipa vai entrar em contacto consigo para combinar o pagamento e a entrega.</p>' . $resumo);
    enviar_email($email, $nome, 'Pedido recebido — ' . $ref, $corpoCliente);

    // Email à Gráfica Lifei.
    $paraLoja = config_get('orders_email', (string) env('MAIL_ORDERS_TO', 'pedidos@graficalifei.co.mz'));
    $corpoLoja = template_email('Novo pedido — ' . e($ref), '
        <p>Foi recebido um novo pedido (<strong>' . e($ref) . '</strong>).</p>' . $resumo . '
        <p style="margin-top:16px;"><a href="' . e(url('admin/pedidos')) . '" style="background:#e0202f;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">Ver no painel</a></p>');
    enviar_email($paraLoja, config_get('site_name', 'Gráfica Lifei'), 'Novo pedido: ' . $ref, $corpoLoja, '', $email);
}

// ------------------------------------------------------------------
//  Vista do formulário
// ------------------------------------------------------------------
$itens    = carrinho_itens();
$subtotal = carrinho_subtotal();
$desconto = carrinho_desconto();
$total    = carrinho_total();

// Pré-preencher com os dados do utilizador autenticado.
$defNome  = old('nome', logado() ? (string) user('nome') : '');
$defEmail = old('email', logado() ? (string) user('email') : '');
$defTel   = old('telefone', logado() ? (string) (user('telefone') ?: '') : '');

$titulo    = 'Fazer pedido';
$descricao = 'Finalize o seu pedido na Gráfica Lifei.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <h1 class="h3 fw-bold mb-4"><i class="bi bi-check2-circle me-2"></i>Fazer pedido</h1>

        <form method="post" action="<?= url('fazer-pedido') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-4">
                <!-- Dados -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Os seus dados</h2>
                            <?php if (! logado()): ?>
                                <p class="small text-muted">Já tem conta? <a href="<?= url('login') ?>">Entre aqui</a> — ou continue como convidado.</p>
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="nome">Nome completo</label>
                                    <input type="text" name="nome" id="nome" class="form-control <?= erro('nome') ? 'is-invalid' : '' ?>" value="<?= e($defNome) ?>" required>
                                    <?php if ($m = erro('nome')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" name="email" id="email" class="form-control <?= erro('email') ? 'is-invalid' : '' ?>" value="<?= e($defEmail) ?>" required>
                                    <?php if ($m = erro('email')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="telefone">WhatsApp / Telefone</label>
                                    <input type="text" name="telefone" id="telefone" class="form-control <?= erro('telefone') ? 'is-invalid' : '' ?>" value="<?= e($defTel) ?>" required>
                                    <?php if ($m = erro('telefone')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tipo_entrega">Entrega</label>
                                    <select name="tipo_entrega" id="tipo_entrega" class="form-select" onchange="document.getElementById('bloco-endereco').classList.toggle('d-none', this.value !== 'entrega')">
                                        <option value="levantamento">Levantamento na loja</option>
                                        <option value="entrega" <?= old('tipo_entrega') === 'entrega' ? 'selected' : '' ?>>Entrega ao domicílio</option>
                                    </select>
                                </div>
                                <div class="col-12 <?= old('tipo_entrega') === 'entrega' ? '' : 'd-none' ?>" id="bloco-endereco">
                                    <label class="form-label" for="endereco">Endereço de entrega</label>
                                    <input type="text" name="endereco" id="endereco" class="form-control <?= erro('endereco') ? 'is-invalid' : '' ?>" value="<?= old('endereco') ?>" placeholder="Rua, número, bairro, cidade">
                                    <?php if ($m = erro('endereco')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="observacoes">Observações <span class="text-muted small">(prazo, detalhes do design, etc.)</span></label>
                                    <textarea name="observacoes" id="observacoes" rows="3" class="form-control"><?= old('obs') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumo -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Resumo do pedido</h2>
                            <ul class="list-unstyled mb-3">
                                <?php foreach ($itens as $it): ?>
                                    <li class="d-flex justify-content-between mb-2 small">
                                        <span><?= (int) $it['quantidade'] ?>× <?= e($it['nome']) ?>
                                            <?php if ($it['opcoes'] !== ''): ?><br><span class="text-muted"><?= e($it['opcoes']) ?></span><?php endif; ?>
                                        </span>
                                        <span class="text-nowrap ms-2"><?= e(moeda($it['subtotal'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span><?= e(moeda($subtotal)) ?></span></div>
                            <?php if ($desconto > 0): ?>
                                <div class="d-flex justify-content-between mb-1 text-success"><span>Desconto</span><span>-<?= e(moeda($desconto)) ?></span></div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between fw-bold fs-5 mt-2"><span>Total</span><span class="text-primary"><?= e(moeda($total)) ?></span></div>

                            <button type="submit" class="btn btn-primary w-100 fw-semibold mt-3">
                                <i class="bi bi-send-check me-1"></i>Confirmar pedido
                            </button>
                            <p class="text-muted small text-center mt-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i>Sem pagamento online. Combinamos consigo por email/WhatsApp.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
