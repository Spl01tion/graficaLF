<?php

/**
 * ============================================================
 *  setup.php — Instalador da base de dados
 * ------------------------------------------------------------
 *  À semelhança do setup.php do MaxaPark, cria toda a estrutura
 *  e insere os dados de exemplo. Acede-se uma vez em:
 *      /setup            -> mostra o estado / botão de instalar
 *      /setup?run=1      -> executa schema + seeds
 *      /setup?run=1&fresh=1 -> APAGA tudo e reinstala de raiz
 *
 *  SEGURANÇA: só corre em ambiente local (APP_ENV=local). Em
 *  produção devolve 403 — nunca deve ficar acessível online.
 * ============================================================
 */

if (env('APP_ENV', 'production') !== 'local') {
    http_response_code(403);
    exit('O instalador está desactivado. Disponível apenas em ambiente local.');
}

$executado = false;
$relatorio = [];
$erro = null;
$fresh = isset($_GET['fresh']);

if (isset($_GET['run'])) {
    try {
        $pdo = conexao();

        // Reinstalação de raiz: apaga as tabelas na ordem inversa das dependências.
        if ($fresh) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ([
                'order_status_history', 'order_items', 'orders', 'cart_items', 'carts',
                'wishlists', 'product_options', 'product_images', 'products',
                'coupons', 'categories', 'brands', 'password_resets', 'users',
                'contact_messages', 'settings',
            ] as $t) {
                $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        // 1) Estrutura (schema.sql). O driver MySQL do PDO executa
        //    várias instruções separadas por ";" numa só chamada.
        $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
        $pdo->exec($schema);

        // 2) Dados de exemplo (seeds.php).
        require BASE_PATH . '/database/seeds.php';
        $relatorio = executar_seeds();

        $executado = true;
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

// Tabelas existentes (para mostrar o estado actual).
$tabelas = [];
try {
    $rows = query('SHOW TABLES');
    if ($rows) {
        foreach ($rows as $r) {
            $tabelas[] = array_values($r)[0];
        }
    }
} catch (Throwable) {
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalador — <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/estilo.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 820px;">
    <div class="text-center mb-4">
        <span class="logo-badge mx-auto d-inline-flex mb-2" style="width:56px;height:56px;font-size:1.4rem;">GL</span>
        <h1 class="h3 fw-bold">Instalador da Base de Dados</h1>
        <p class="text-muted">Módulo 1 — cria a estrutura e insere os dados de exemplo.</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><i class="bi bi-x-octagon me-2"></i><strong>Erro:</strong> <?= e($erro) ?></div>
    <?php endif; ?>

    <?php if ($executado): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i><strong>Instalação concluída com sucesso!</strong>
        </div>
        <div class="card mb-4">
            <div class="card-header fw-semibold">Registos inseridos</div>
            <ul class="list-group list-group-flush">
                <?php if ($relatorio === []): ?>
                    <li class="list-group-item text-muted">Nada de novo — os dados já existiam (execução idempotente).</li>
                <?php else: ?>
                    <?php foreach ($relatorio as $tabela => $n): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= e($tabela) ?></span>
                            <span class="badge bg-primary rounded-pill"><?= (int) $n ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="alert alert-warning small">
            <i class="bi bi-shield-lock me-1"></i>
            <strong>Credenciais de acesso (altere depois):</strong><br>
            Admin — <code>admin@graficalifei.co.mz</code> / <code>admin123</code><br>
            Cliente — <code>cliente@exemplo.co.mz</code> / <code>cliente123</code>
        </div>
        <a href="<?= url('home') ?>" class="btn btn-primary"><i class="bi bi-house-door me-1"></i>Ir para o site</a>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header fw-semibold">Estado actual</div>
            <div class="card-body">
                <?php if ($tabelas === []): ?>
                    <p class="mb-0 text-muted">Base de dados <code><?= e(DBNAME) ?></code> vazia — pronta para instalar.</p>
                <?php else: ?>
                    <p class="mb-2">A base de dados <code><?= e(DBNAME) ?></code> tem <?= count($tabelas) ?> tabela(s):</p>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($tabelas as $t): ?>
                            <span class="badge bg-secondary"><?= e($t) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('setup?run=1') ?>" class="btn btn-primary">
                <i class="bi bi-database-add me-1"></i>Instalar / Actualizar
            </a>
            <a href="<?= url('setup?run=1&fresh=1') ?>" class="btn btn-outline-danger"
               onclick="return confirm('Isto APAGA todas as tabelas e reinstala de raiz. Continuar?');">
                <i class="bi bi-trash me-1"></i>Reinstalar de raiz (apaga tudo)
            </a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
