<?php

/**
 * login.php — Início de sessão.
 *
 * Padrão das páginas de autenticação: a lógica de controlo (POST) fica
 * no topo, ANTES de qualquer HTML (para poder redireccionar/definir
 * cabeçalhos), e a vista (formulário) vem a seguir.
 */

// Um utilizador já autenticado não precisa de ver o login.
if (logado()) {
    redirect(destino_pos_login());
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) CSRF — bloqueia submissões forjadas de outros sites.
    csrf_verificar();

    // 2) Rate limiting — trava tentativas de força bruta.
    if (! rate_limit('login', RATE_LIMIT_LOGIN, RATE_LIMIT_WINDOW)) {
        $espera = ceil(rate_limit_espera('login', RATE_LIMIT_WINDOW) / 60);
        $errors['geral'] = "Demasiadas tentativas. Tente novamente dentro de {$espera} minuto(s).";
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 3) Validação de presença.
    if ($errors === []) {
        if ($email === '') {
            $errors['email'] = 'Indique o seu email.';
        }
        if ($password === '') {
            $errors['password'] = 'Indique a sua senha.';
        }
    }

    // 4) Verificação das credenciais.
    if ($errors === []) {
        $utilizador = query_row('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);

        // Mensagem genérica (não revelar se o email existe ou não).
        if (! $utilizador || ! password_verify($password, $utilizador['password'])) {
            $errors['geral'] = 'Email ou senha incorrectos.';
        } else {
            // Sucesso: regista na sessão (regenera o ID) e redirecciona.
            authenticate($utilizador);
            wishlist_carregar_da_bd(); // reúne a wishlist guardada anteriormente
            unset($_SESSION['_rate']['login']); // limpa o contador de tentativas
            flash('sucesso', 'Bem-vindo de volta, ' . $utilizador['nome'] . '!');
            redirect(destino_pos_login());
        }
    }

    guardar_erros($errors, ['email' => $email]);
}

$titulo    = 'Entrar';
$descricao = 'Inicie sessão na sua conta Gráfica Lifei.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-4" style="max-width: 460px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold mb-1">Entrar</h1>
                    <p class="text-muted small mb-0">Aceda à sua conta para acompanhar os seus pedidos.</p>
                </div>

                <?php if ($msg = erro('geral')): ?>
                    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= e($msg) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= url('login') ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control <?= erro('email') ? 'is-invalid' : '' ?>"
                               value="<?= old('email') ?>" autofocus required>
                        <?php if ($m = erro('email')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="password">Senha</label>
                        <input type="password" name="password" id="password"
                               class="form-control <?= erro('password') ? 'is-invalid' : '' ?>" required>
                        <?php if ($m = erro('password')): ?><div class="invalid-feedback"><?= e($m) ?></div><?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end mb-3">
                        <a href="<?= url('recuperar') ?>" class="small text-decoration-none">Esqueceu-se da senha?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                    </button>
                </form>

                <hr class="my-4">
                <p class="text-center small mb-0">
                    Ainda não tem conta?
                    <a href="<?= url('registar') ?>" class="fw-semibold text-decoration-none">Criar conta</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
