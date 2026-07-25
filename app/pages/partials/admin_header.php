<?php

/**
 * admin_header.php — Layout do painel (sidebar + topbar).
 * Cada página de admin inclui este ficheiro e o admin_footer.php.
 *
 * A secção activa é derivada de $url[1] para destacar o menu.
 */

$secao = $url[1] ?? 'dashboard';
$tituloPainel = $tituloPainel ?? 'Dashboard';

$menu = [
    ''             => ['Dashboard', 'bi-speedometer2'],
    'produtos'     => ['Produtos', 'bi-box-seam'],
    'categorias'   => ['Categorias', 'bi-tags'],
    'marcas'       => ['Marcas', 'bi-award'],
    'pedidos'      => ['Pedidos', 'bi-receipt'],
    'cupoes'       => ['Cupões', 'bi-ticket-perforated'],
    'utilizadores' => ['Utilizadores', 'bi-people'],
    'mensagens'    => ['Mensagens', 'bi-envelope'],
    'definicoes'   => ['Definições', 'bi-gear'],
];

$flash = get_flash();
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPainel) ?> — Painel <?= e(APP_NAME) ?></title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/estilo.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">

    <!-- Sidebar -->
    <nav class="admin-sidebar" id="adminSidebar">
        <div class="p-3 border-bottom border-secondary border-opacity-25">
            <a href="<?= url('admin') ?>" class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold mb-0">
                <span class="logo-badge">GL</span><span>Painel Lifei</span>
            </a>
        </div>
        <div class="py-2">
            <?php foreach ($menu as $slug => [$rotulo, $icon]): ?>
                <a href="<?= url('admin' . ($slug ? '/' . $slug : '')) ?>" class="<?= $secao === ($slug ?: 'dashboard') || ($slug === '' && $secao === 'dashboard') ? 'active' : '' ?>">
                    <i class="bi <?= $icon ?>"></i><?= e($rotulo) ?>
                </a>
            <?php endforeach; ?>
            <hr class="text-secondary mx-3">
            <a href="<?= url('home') ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i>Ver o site</a>
            <form action="<?= url('logout') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="border-0 bg-transparent w-100 text-start" style="color:rgba(255,255,255,.72);padding:.7rem 1.25rem;">
                    <i class="bi bi-box-arrow-right me-2"></i>Terminar sessão
                </button>
            </form>
        </div>
    </nav>

    <!-- Conteúdo -->
    <div class="admin-content">
        <!-- Topbar -->
        <div class="bg-white shadow-sm sticky-top">
            <div class="d-flex justify-content-between align-items-center px-3 px-md-4 py-2">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.getElementById('adminSidebar').classList.toggle('aberta')">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="h5 fw-bold mb-0"><?= e($tituloPainel) ?></h1>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= e(user('nome')) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= url('conta') ?>">A minha conta</a></li>
                        <li><a class="dropdown-item" href="<?= url('home') ?>" target="_blank">Ver o site</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <?php foreach ($flash as $tipo => $msg): ?>
                <div class="alert alert-<?= $tipo === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show">
                    <?= e($msg) ?>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
