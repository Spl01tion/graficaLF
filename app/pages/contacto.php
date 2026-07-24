<?php

/**
 * contacto.php — Página de contacto + processamento do formulário.
 *
 * Ao submeter (POST):
 *   1. valida CSRF e aplica rate limiting (anti-spam)
 *   2. valida os campos server-side
 *   3. grava em contact_messages
 *   4. envia email à Gráfica Lifei (PHPMailer)
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verificar();

    $errors = [];

    if (! rate_limit('contacto', RATE_LIMIT_CONTACT, RATE_LIMIT_WINDOW)) {
        $espera = ceil(rate_limit_espera('contacto', RATE_LIMIT_WINDOW) / 60);
        flash('erro', "Demasiadas mensagens. Tente novamente dentro de {$espera} minuto(s).");
        redirect('contacto');
    }

    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($nome === '' || mb_strlen($nome) < 3) {
        $errors['nome'] = 'Indique o seu nome.';
    }
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido.';
    }
    if ($telefone !== '' && ! preg_match('/^[0-9+\s()-]{6,30}$/', $telefone)) {
        $errors['telefone'] = 'Telefone inválido.';
    }
    if ($mensagem === '' || mb_strlen($mensagem) < 10) {
        $errors['mensagem'] = 'Escreva uma mensagem com pelo menos 10 caracteres.';
    }

    if ($errors === []) {
        // 1) Guardar na base de dados.
        inserir('contact_messages', [
            'nome'     => $nome,
            'email'    => $email,
            'telefone' => $telefone !== '' ? $telefone : null,
            'mensagem' => $mensagem,
        ]);

        // 2) Notificar a Gráfica Lifei por email.
        $para = config_get('site_email', (string) env('MAIL_CONTACT_TO', 'geral@graficalifei.co.mz'));
        $corpo = template_email('Nova mensagem de contacto', '
            <p><strong>Nome:</strong> ' . e($nome) . '</p>
            <p><strong>Email:</strong> ' . e($email) . '</p>
            <p><strong>Telefone:</strong> ' . e($telefone !== '' ? $telefone : '—') . '</p>
            <p><strong>Mensagem:</strong></p>
            <p style="background:#f4f6f8;padding:12px;border-radius:8px;">' . nl2br(e($mensagem)) . '</p>
        ');
        enviar_email($para, config_get('site_name', 'Gráfica Lifei'), 'Contacto: ' . $nome, $corpo, '', $email);

        flash('sucesso', 'Mensagem enviada! Entraremos em contacto brevemente.');
        redirect('contacto');
    }

    guardar_erros($errors, compact('nome', 'email', 'telefone', 'mensagem'));
    redirect('contacto');
}

$titulo    = 'Contacto';
$descricao = 'Fale com a Gráfica Lifei — orçamentos e informações.';
require __DIR__ . '/partials/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Fale connosco</h1>
            <p class="text-muted">Estamos prontos para dar vida ao seu próximo projecto.</p>
        </div>

        <div class="row g-5">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 fw-bold mb-4">Envie-nos uma mensagem</h2>
                        <?php require __DIR__ . '/partials/form-contacto.php'; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm mb-4">
                    <iframe src="<?= e(config_get('maps_embed', 'https://www.google.com/maps?q=Maputo&output=embed')) ?>"
                            style="border:0;" allowfullscreen loading="lazy"></iframe>
                </div>

                <div class="list-group list-group-flush">
                    <div class="list-group-item bg-transparent px-0 d-flex gap-3">
                        <i class="bi bi-geo-alt-fill text-primary fs-4"></i>
                        <div><strong>Morada</strong><br><span class="text-muted"><?= e(config_get('site_address', 'Maputo, Moçambique')) ?></span></div>
                    </div>
                    <div class="list-group-item bg-transparent px-0 d-flex gap-3">
                        <i class="bi bi-telephone-fill text-primary fs-4"></i>
                        <div><strong>Telefone</strong><br><span class="text-muted"><?= e(config_get('site_phone', '+258 84 000 0000')) ?></span></div>
                    </div>
                    <div class="list-group-item bg-transparent px-0 d-flex gap-3">
                        <i class="bi bi-envelope-fill text-primary fs-4"></i>
                        <div><strong>Email</strong><br><span class="text-muted"><?= e(config_get('site_email', 'geral@graficalifei.co.mz')) ?></span></div>
                    </div>
                    <div class="list-group-item bg-transparent px-0 d-flex gap-3">
                        <i class="bi bi-clock-fill text-primary fs-4"></i>
                        <div><strong>Horário</strong><br><span class="text-muted">Segunda a Sexta: 08h–17h · Sábado: 08h–13h</span></div>
                    </div>
                    <div class="list-group-item bg-transparent px-0 d-flex gap-3">
                        <i class="bi bi-share-fill text-primary fs-4"></i>
                        <div>
                            <strong>Redes sociais</strong><br>
                            <a href="<?= e(config_get('facebook_url', '#')) ?>" class="text-decoration-none me-2"><i class="bi bi-facebook"></i></a>
                            <a href="<?= e(config_get('instagram_url', '#')) ?>" class="text-decoration-none me-2"><i class="bi bi-instagram"></i></a>
                            <a href="<?= e(config_get('whatsapp_url', '#')) ?>" class="text-decoration-none"><i class="bi bi-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
