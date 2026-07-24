<?php

/**
 * recuperar.php — Pedido de recuperação de senha.
 *
 * O utilizador indica o email; se existir, geramos um token, guardamo-lo
 * (em hash) e enviamos um link de redefinição por email.
 *
 * Segurança:
 *  - Resposta genérica: nunca revelamos se o email existe (anti-enumeração).
 *  - O token é aleatório (32 bytes) e guardado em hash (SHA-256).
 *  - Validade curta (1 hora).
 *  - Rate limiting para evitar abuso/spam.
 */

if (logado()) {
    redirect(destino_pos_login());
}

$errors = [];
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verificar();

    if (! rate_limit('recuperar', RATE_LIMIT_CONTACT, RATE_LIMIT_WINDOW)) {
        $espera = ceil(rate_limit_espera('recuperar', RATE_LIMIT_WINDOW) / 60);
        $errors['geral'] = "Demasiados pedidos. Tente novamente dentro de {$espera} minuto(s).";
    }

    $email = trim($_POST['email'] ?? '');

    if ($errors === []) {
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Indique um email válido.';
        }
    }

    if ($errors === []) {
        $utilizador = query_row('SELECT id_user, nome FROM users WHERE email = :e LIMIT 1', ['e' => $email]);

        // Só enviamos se o utilizador existir — mas a mensagem ao ecrã é
        // sempre a mesma, exista ou não.
        if ($utilizador) {
            $token = bin2hex(random_bytes(32));

            // Invalida pedidos anteriores para este email.
            execute('DELETE FROM password_resets WHERE email = :e', ['e' => $email]);

            inserir('password_resets', [
                'email'      => $email,
                'token'      => hash('sha256', $token),   // guardamos o hash, não o token
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);

            $link = url('redefinir?token=' . $token);
            $corpo = template_email('Recuperação de senha', '
                <p>Olá ' . e($utilizador['nome']) . ',</p>
                <p>Recebemos um pedido para redefinir a senha da sua conta. Clique no botão abaixo (válido por 1 hora):</p>
                <p style="text-align:center; margin: 24px 0;">
                    <a href="' . e($link) . '" style="background:#e0202f; color:#fff; padding:12px 28px; border-radius:8px; text-decoration:none; font-weight:bold;">Redefinir senha</a>
                </p>
                <p style="font-size:13px; color:#667;">Se não foi você, ignore este email — a sua senha permanece inalterada.</p>
            ');

            enviar_email($email, $utilizador['nome'], 'Recuperação de senha — Gráfica Lifei', $corpo);
        }

        $enviado = true;
    } else {
        guardar_erros($errors, ['email' => $email]);
    }
}

$titulo    = 'Recuperar senha';
$descricao = 'Recupere o acesso à sua conta.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-4" style="max-width: 460px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold mb-1">Recuperar senha</h1>
                    <p class="text-muted small mb-0">Enviamos-lhe um link para redefinir a senha.</p>
                </div>

                <?php if ($enviado): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-envelope-check me-1"></i>
                        Se existir uma conta associada a esse email, enviámos um link de recuperação.
                        Verifique a sua caixa de entrada.
                    </div>
                    <a href="<?= url('login') ?>" class="btn btn-outline-secondary w-100">Voltar ao login</a>
                <?php else: ?>
                    <?php if ($msg = erro('geral')): ?>
                        <div class="alert alert-danger py-2"><?= e($msg) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= url('recuperar') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email da conta</label>
                            <input type="email" name="email" id="email"
                                   class="form-control <?= erro('email') ? 'is-invalid' : '' ?>"
                                   value="<?= old('email') ?>" autofocus required>
                            <?php if ($m = erro('email')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-send me-1"></i>Enviar link de recuperação
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center small mb-0">
                        <a href="<?= url('login') ?>" class="text-decoration-none">Voltar ao login</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
