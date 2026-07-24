<?php

/**
 * admin.php — Router do painel de administração.
 *
 * Protege TODA a área com controlo_admin() (RBAC) e encaminha para a
 * secção pedida: /admin/{seccao} -> app/pages/admin/{seccao}.php
 * (mesmo padrão do MaxaPark).
 */

controlo_admin();

$secao = $url[1] ?? 'dashboard';
$secao = $secao === '' ? 'dashboard' : preg_replace('/[^a-z0-9_-]/', '', $secao);

$ficheiro = __DIR__ . '/admin/' . $secao . '.php';

if (! is_file($ficheiro)) {
    $ficheiro = __DIR__ . '/admin/dashboard.php';
}

require $ficheiro;
