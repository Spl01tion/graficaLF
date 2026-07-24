<?php

/**
 * registar.php — Criação de conta de cliente.
 *
 * Regista sempre com o papel 'customer'. A criação de administradores
 * faz-se pelo painel (Módulo 7), nunca por auto-registo.
 */

if (logado()) {
    redirect(destino_pos_login());
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verificar();

    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    // --- Validação server-side ---
    if ($nome === '') {
        $errors['nome'] = 'Indique o seu nome.';
    } elseif (mb_strlen($nome) < 3) {
        $errors['nome'] = 'O nome deve ter pelo menos 3 caracteres.';
    }

    if ($email === '') {
        $errors['email'] = 'Indique o seu email.';
    } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido.';
    } elseif (query_row('SELECT id_user FROM users WHERE email = :e LIMIT 1', ['e' => $email])) {
        $errors['email'] = 'Este email já está registado.';
    }

    if ($telefone !== '' && ! preg_match('/^[0-9+\s()-]{6,30}$/', $telefone)) {
        $errors['telefone'] = 'Telefone inválido.';
    }

    if ($password === '') {
        $errors['password'] = 'Defina uma senha.';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirmar) {
        $errors['confirmar'] = 'As senhas não coincidem.';
    }

    // --- Criação da conta ---
    if ($errors === []) {
        $id = inserir('users', [
            'nome'     => $nome,
            'email'    => $email,
            'telefone' => $telefone !== '' ? $telefone : null,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => 'customer',
        ]);

        $utilizador = query_row('SELECT * FROM users WHERE id_user = :id', ['id' => $id]);
        authenticate($utilizador);
        flash('sucesso', 'Conta criada com sucesso. Bem-vindo, ' . $nome . '!');
        redirect('home');
    }

    guardar_erros($errors, compact('nome', 'email', 'telefone'));
}

$titulo    = 'Criar conta';
$descricao = 'Crie a sua conta na Gráfica Lifei.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-4" style="max-width: 520px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold mb-1">Criar conta</h1>
                    <p class="text-muted small mb-0">Registe-se para fazer pedidos e guardar favoritos.</p>
                </div>

                <form method="post" action="<?= url('registar') ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="nome">Nome completo</label>
                        <input type="text" name="nome" id="nome"
                               class="form-control <?= erro('nome') ? 'is-invalid' : '' ?>"
                               value="<?= old('nome') ?>" required>
                        <?php if ($m = erro('nome')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control <?= erro('email') ? 'is-invalid' : '' ?>"
                               value="<?= old('email') ?>" required>
                        <?php if ($m = erro('email')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="telefone">Telefone / WhatsApp <span class="text-muted small">(opcional)</span></label>
                        <input type="text" name="telefone" id="telefone"
                               class="form-control <?= erro('telefone') ? 'is-invalid' : '' ?>"
                               value="<?= old('telefone') ?>" placeholder="+258 84 000 0000">
                        <?php if ($m = erro('telefone')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Senha</label>
                            <input type="password" name="password" id="password"
                                   class="form-control <?= erro('password') ? 'is-invalid' : '' ?>" required>
                            <?php if ($m = erro('password')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="confirmar">Confirmar senha</label>
                            <input type="password" name="confirmar" id="confirmar"
                                   class="form-control <?= erro('confirmar') ? 'is-invalid' : '' ?>" required>
                            <?php if ($m = erro('confirmar')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold mt-4">
                        <i class="bi bi-person-plus me-1"></i>Criar conta
                    </button>
                </form>

                <hr class="my-4">
                <p class="text-center small mb-0">
                    Já tem conta?
                    <a href="<?= url('login') ?>" class="fw-semibold text-decoration-none">Entrar</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
