<?php

/**
 * redefinir.php — Definição de uma nova senha a partir do link de recuperação.
 *
 * O token chega no URL (?token=...). Comparamos o seu hash com o que está
 * guardado e verificamos a validade. Se estiver correcto e dentro do prazo,
 * permitimos definir uma nova senha.
 */

if (logado()) {
    redirect(destino_pos_login());
}

/** Procura um registo de recuperação válido (não expirado) para o token dado. */
function reset_valido(string $token): array|false
{
    if ($token === '') {
        return false;
    }

    return query_row(
        'SELECT * FROM password_resets WHERE token = :t AND expires_at > NOW() LIMIT 1',
        ['t' => hash('sha256', $token)]
    );
}

$errors = [];
$token  = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$registo = reset_valido($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verificar();

    $password  = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (! $registo) {
        $errors['geral'] = 'O link de recuperação é inválido ou expirou. Peça um novo.';
    }

    if ($errors === []) {
        if ($password === '' || mb_strlen($password) < 6) {
            $errors['password'] = 'A senha deve ter pelo menos 6 caracteres.';
        } elseif ($password !== $confirmar) {
            $errors['confirmar'] = 'As senhas não coincidem.';
        }
    }

    if ($errors === []) {
        // Actualiza a senha do utilizador dono do email do token.
        execute(
            'UPDATE users SET password = :p WHERE email = :e',
            ['p' => password_hash($password, PASSWORD_DEFAULT), 'e' => $registo['email']]
        );

        // Consome todos os tokens desse email (não reutilizáveis).
        execute('DELETE FROM password_resets WHERE email = :e', ['e' => $registo['email']]);

        flash('sucesso', 'Senha redefinida com sucesso. Já pode entrar.');
        redirect('login');
    }

    guardar_erros($errors);
}

$titulo    = 'Redefinir senha';
$descricao = 'Defina uma nova senha.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-4" style="max-width: 460px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold mb-1">Redefinir senha</h1>
                    <p class="text-muted small mb-0">Escolha uma nova senha para a sua conta.</p>
                </div>

                <?php if (! $registo && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-x-octagon me-1"></i>
                        O link de recuperação é inválido ou expirou.
                    </div>
                    <a href="<?= url('recuperar') ?>" class="btn btn-primary w-100">Pedir novo link</a>
                <?php else: ?>
                    <?php if ($msg = erro('geral')): ?>
                        <div class="alert alert-danger py-2"><?= e($msg) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= url('redefinir') ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= e($token) ?>">

                        <div class="mb-3">
                            <label class="form-label" for="password">Nova senha</label>
                            <input type="password" name="password" id="password"
                                   class="form-control <?= erro('password') ? 'is-invalid' : '' ?>" autofocus required>
                            <?php if ($m = erro('password')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="confirmar">Confirmar nova senha</label>
                            <input type="password" name="confirmar" id="confirmar"
                                   class="form-control <?= erro('confirmar') ? 'is-invalid' : '' ?>" required>
                            <?php if ($m = erro('confirmar')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-shield-check me-1"></i>Redefinir senha
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
