<?php

/**
 * admin/mensagens.php — Inbox do formulário de contacto.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($acao === 'ler') {
        execute('UPDATE contact_messages SET lida=1 WHERE id_message=:id', ['id' => $id]);
    } elseif ($acao === 'apagar') {
        execute('DELETE FROM contact_messages WHERE id_message=:id', ['id' => $id]);
        flash('sucesso', 'Mensagem apagada.');
    }
    redirect('admin/mensagens');
}

$mensagens = query('SELECT * FROM contact_messages ORDER BY created_at DESC') ?: [];

$tituloPainel = 'Mensagens';
require __DIR__ . '/../partials/admin_header.php';
?>

<p class="text-muted mb-3"><?= count($mensagens) ?> mensagem(ns)</p>

<div class="row g-3">
    <?php foreach ($mensagens as $m): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm <?= $m['lida'] ? '' : 'border-start border-primary border-3' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="h6 fw-bold mb-1">
                                <?= e($m['nome']) ?>
                                <?php if (! $m['lida']): ?><span class="badge bg-primary ms-1">Nova</span><?php endif; ?>
                            </h3>
                            <p class="small text-muted mb-2">
                                <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
                                <?php if ($m['telefone']): ?> · <?= e($m['telefone']) ?><?php endif; ?>
                                · <?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?>
                            </p>
                        </div>
                        <div class="text-nowrap">
                            <?php if (! $m['lida']): ?>
                                <form method="post" action="<?= url('admin/mensagens') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="acao" value="ler">
                                    <input type="hidden" name="id" value="<?= (int) $m['id_message'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" title="Marcar como lida"><i class="bi bi-check2"></i></button>
                                </form>
                            <?php endif; ?>
                            <a href="mailto:<?= e($m['email']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-reply"></i></a>
                            <form method="post" action="<?= url('admin/mensagens') ?>" class="d-inline" onsubmit="return confirm('Apagar esta mensagem?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="apagar">
                                <input type="hidden" name="id" value="<?= (int) $m['id_message'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <p class="mb-0"><?= nl2br(e($m['mensagem'])) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($mensagens === []): ?>
        <div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox display-4"></i><p class="mt-3">Sem mensagens.</p></div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
