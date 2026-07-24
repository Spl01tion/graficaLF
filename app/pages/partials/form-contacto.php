<?php

/**
 * form-contacto.php — Formulário de contacto (reutilizado na home e em /contacto).
 * Submete para /contacto (processado por contacto.php, com PHPMailer + BD).
 */
?>
<form method="post" action="<?= url('contacto') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="c_nome">Nome</label>
            <input type="text" name="nome" id="c_nome"
                   class="form-control <?= erro('nome') ? 'is-invalid' : '' ?>" value="<?= old('nome') ?>" required>
            <?php if ($m = erro('nome')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="c_email">Email</label>
            <input type="email" name="email" id="c_email"
                   class="form-control <?= erro('email') ? 'is-invalid' : '' ?>" value="<?= old('email') ?>" required>
            <?php if ($m = erro('email')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="c_telefone">WhatsApp / Telefone</label>
            <input type="text" name="telefone" id="c_telefone"
                   class="form-control <?= erro('telefone') ? 'is-invalid' : '' ?>" value="<?= old('telefone') ?>"
                   placeholder="+258 84 000 0000">
            <?php if ($m = erro('telefone')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="c_mensagem">Mensagem</label>
            <textarea name="mensagem" id="c_mensagem" rows="4"
                      class="form-control <?= erro('mensagem') ? 'is-invalid' : '' ?>" required><?= old('mensagem') ?></textarea>
            <?php if ($m = erro('mensagem')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary fw-semibold px-4">
                <i class="bi bi-send me-1"></i>Enviar mensagem
            </button>
        </div>
    </div>
</form>
