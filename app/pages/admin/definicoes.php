<?php

/**
 * admin/definicoes.php — Configurações gerais do site (tabela settings).
 *
 * Guarda pares chave-valor: contactos, redes sociais, textos promocionais,
 * email de destino dos pedidos, mapa, etc.
 */

/** Grava (ou actualiza) uma definição na tabela settings. */
function definicao_set(string $chave, string $valor): void
{
    execute(
        'INSERT INTO settings (`key`, `value`) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE `value` = :v2',
        ['k' => $chave, 'v' => $valor, 'v2' => $valor]
    );
}

// Campos editáveis (chave => rótulo).
$campos = [
    'site_name'       => 'Nome do site',
    'site_email'      => 'Email geral',
    'site_phone'      => 'Telefone',
    'site_whatsapp'   => 'WhatsApp (só números)',
    'site_address'    => 'Morada',
    'orders_email'    => 'Email de destino dos pedidos',
    'promo_text'      => 'Texto promocional (barra superior)',
    'free_shipping_min' => 'Valor mínimo para entrega grátis (MZN)',
    'facebook_url'    => 'URL Facebook',
    'instagram_url'   => 'URL Instagram',
    'whatsapp_url'    => 'URL WhatsApp',
    'maps_embed'      => 'URL do mapa (Google Maps embed)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    foreach ($campos as $chave => $_) {
        if (array_key_exists($chave, $_POST)) {
            definicao_set($chave, trim((string) $_POST[$chave]));
        }
    }
    flash('sucesso', 'Definições guardadas.');
    redirect('admin/definicoes');
}

$tituloPainel = 'Definições';
require __DIR__ . '/../partials/admin_header.php';
?>

<form method="post" action="<?= url('admin/definicoes') ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Informação geral e contactos</h2>
                    <div class="row g-3">
                        <?php foreach ($campos as $chave => $rotulo): ?>
                            <div class="col-md-6">
                                <label class="form-label small"><?= e($rotulo) ?></label>
                                <?php if ($chave === 'promo_text' || $chave === 'maps_embed'): ?>
                                    <textarea name="<?= e($chave) ?>" rows="2" class="form-control"><?= e(config_get($chave, '')) ?></textarea>
                                <?php else: ?>
                                    <input type="text" name="<?= e($chave) ?>" class="form-control" value="<?= e(config_get($chave, '')) ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-2">SMTP / Email</h2>
                    <p class="small text-muted">
                        As credenciais de envio de email (SMTP) são configuradas no ficheiro
                        <code>.env</code> por segurança, e não aqui. Enquanto o SMTP não estiver
                        configurado, os emails são guardados em
                        <code>storage/logs/emails/</code> para teste.
                    </p>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold mt-2"><i class="bi bi-check-lg me-1"></i>Guardar definições</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
