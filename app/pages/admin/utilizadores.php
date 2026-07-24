<?php

/**
 * admin/utilizadores.php — Gestão de utilizadores (criar, editar, role, remover).
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'guardar') {
        $id       = (int) ($_POST['id'] ?? 0);
        $nome     = trim($_POST['nome'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $role     = ($_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
        $password = $_POST['password'] ?? '';

        $erros = [];
        if ($nome === '' || mb_strlen($nome) < 3) { $erros[] = 'Nome inválido.'; }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = 'Email inválido.'; }
        if (query_row('SELECT id_user FROM users WHERE email=:e AND id_user<>:id', ['e' => $email, 'id' => $id])) { $erros[] = 'Email já registado.'; }
        if ($id === 0 && mb_strlen($password) < 6) { $erros[] = 'A senha deve ter pelo menos 6 caracteres.'; }

        if ($erros !== []) {
            flash('erro', implode(' ', $erros));
        } else {
            if ($id > 0) {
                execute('UPDATE users SET nome=:n, email=:e, telefone=:t, role=:r WHERE id_user=:id',
                    ['n' => $nome, 'e' => $email, 't' => $telefone ?: null, 'r' => $role, 'id' => $id]);
                // Alteração de senha é opcional na edição.
                if ($password !== '') {
                    if (mb_strlen($password) < 6) {
                        flash('erro', 'A nova senha deve ter pelo menos 6 caracteres.');
                        redirect('admin/utilizadores');
                    }
                    execute('UPDATE users SET password=:p WHERE id_user=:id',
                        ['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
                }
                flash('sucesso', 'Utilizador actualizado.');
            } else {
                inserir('users', [
                    'nome' => $nome, 'email' => $email, 'telefone' => $telefone ?: null,
                    'password' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role,
                ]);
                flash('sucesso', 'Utilizador criado.');
            }
        }
    } elseif ($acao === 'apagar') {
        $id = (int) $_POST['id'];
        // Não permitir apagar a própria conta (evita ficar sem admin activo).
        if ($id === (int) user('id_user')) {
            flash('erro', 'Não pode apagar a sua própria conta.');
        } else {
            execute('DELETE FROM users WHERE id_user=:id', ['id' => $id]);
            flash('sucesso', 'Utilizador removido.');
        }
    }

    redirect('admin/utilizadores');
}

$utilizadores = query('SELECT * FROM users ORDER BY created_at DESC') ?: [];

$tituloPainel = 'Utilizadores';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= count($utilizadores) ?> utilizador(es)</p>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="prepararUser()"><i class="bi bi-plus-lg me-1"></i>Novo utilizador</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Perfil</th><th>Registo</th><th class="text-end">Acções</th></tr></thead>
            <tbody>
                <?php foreach ($utilizadores as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($u['nome']) ?></td>
                        <td class="small"><?= e($u['email']) ?></td>
                        <td class="small"><?= e($u['telefone'] ?: '—') ?></td>
                        <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Cliente' ?></span></td>
                        <td class="small"><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary" onclick='prepararUser(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#modalUser"><i class="bi bi-pencil"></i></button>
                            <?php if ((int) $u['id_user'] !== (int) user('id_user')): ?>
                                <form method="post" action="<?= url('admin/utilizadores') ?>" class="d-inline" onsubmit="return confirm('Remover este utilizador?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="acao" value="apagar">
                                    <input type="hidden" name="id" value="<?= (int) $u['id_user'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= url('admin/utilizadores') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="acao" value="guardar">
            <input type="hidden" name="id" id="u-id">
            <div class="modal-header">
                <h5 class="modal-title" id="u-titulo">Novo utilizador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="nome" id="u-nome" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="u-email" class="form-control" required></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Telefone</label><input type="text" name="telefone" id="u-tel" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Perfil</label>
                        <select name="role" id="u-role" class="form-select"><option value="customer">Cliente</option><option value="admin">Admin</option></select>
                    </div>
                </div>
                <div class="mt-3"><label class="form-label">Senha <span class="text-muted small" id="u-pass-hint">(deixe vazio para não alterar)</span></label>
                    <input type="password" name="password" id="u-pass" class="form-control"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function prepararUser(u = null) {
        document.getElementById('u-id').value    = u ? u.id_user : '';
        document.getElementById('u-nome').value  = u ? u.nome : '';
        document.getElementById('u-email').value = u ? u.email : '';
        document.getElementById('u-tel').value   = u ? (u.telefone || '') : '';
        document.getElementById('u-role').value  = u ? u.role : 'customer';
        document.getElementById('u-pass').value  = '';
        document.getElementById('u-pass-hint').style.display = u ? 'inline' : 'none';
        document.getElementById('u-titulo').textContent = u ? 'Editar utilizador' : 'Novo utilizador';
    }
</script>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
