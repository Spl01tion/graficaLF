<?php

/**
 * header.php — Topo comum a todas as páginas públicas.
 *
 * Abre <html>, o <head> (com SEO/Open Graph), a barra superior (top strip),
 * a navbar do Bootstrap e o offcanvas do carrinho. É fechado por footer.php.
 *
 * Cada página define, antes de incluir este ficheiro:
 *   $titulo    (string) — título para SEO
 *   $descricao (string) — meta description
 */

$titulo    = $titulo    ?? 'Impressão e Design Gráfico em Moçambique';
$descricao = $descricao ?? 'Gráfica Lifei — impressão de qualidade em Maputo: cartões de visita, banners, t-shirts, stickers, catálogos e serviços de design.';
$flash     = get_flash();
?>
<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($titulo) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e($descricao) ?>">
    <link rel="canonical" href="<?= e(url($_GET['url'] ?? '')) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <meta property="og:title" content="<?= e($titulo) ?>">
    <meta property="og:description" content="<?= e($descricao) ?>">
    <meta property="og:image" content="<?= e(asset('img/og-image.jpg')) ?>">
    <meta property="og:locale" content="pt_MZ">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">

    <!-- Google Fonts: Sora (títulos) + Inter (texto) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 + Icons (via CDN — permitido pelo enunciado) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Folha de estilo do projecto (paleta + customizações) -->
    <link href="<?= asset('css/estilo.css') ?>" rel="stylesheet">
</head>

<body>

    <!-- ============ TOP STRIP ============ -->
    <div class="top-strip text-white">
        <div class="container d-flex flex-wrap justify-content-center justify-content-md-between align-items-center gap-2 py-2 small">
            <div class="d-none d-md-flex align-items-center gap-3">
                <span><i class="bi bi-telephone-fill me-1"></i><?= e(config_get('site_phone', '+258 84 000 0000')) ?></span>
                <span><i class="bi bi-envelope-fill me-1"></i><?= e(config_get('site_email', 'geral@graficalifei.co.mz')) ?></span>
            </div>
            <div class="text-center fw-semibold">
                <i class="bi bi-truck me-1"></i>Entrega grátis em Maputo para pedidos acima de <?= e(moeda(5000)) ?>
            </div>
            <div class="d-none d-md-flex align-items-center gap-2">
                <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                <a href="#" class="text-white"><i class="bi bi-whatsapp"></i></a>
            </div>
        </div>
    </div>

    <!-- ============ NAVBAR (sticky) ============ -->
    <nav class="navbar navbar-expand-lg bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= url('home') ?>">
                <span class="logo-badge">GL</span>
                <span class="logo-texto"><?= e(APP_NAME) ?></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                    aria-controls="navMenu" aria-expanded="false" aria-label="Alternar navegação">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?= nav_activo('home') ?>" href="<?= url('home') ?>">Início</a></li>
                    <li class="nav-item"><a class="nav-link <?= nav_activo('shop') ?>" href="<?= url('shop') ?>">Shop</a></li>
                    <li class="nav-item"><a class="nav-link <?= nav_activo('servicos') ?>" href="<?= url('servicos') ?>">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link <?= nav_activo('sobre') ?>" href="<?= url('sobre') ?>">Sobre</a></li>
                    <li class="nav-item"><a class="nav-link <?= nav_activo('contacto') ?>" href="<?= url('contacto') ?>">Contacto</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <!-- Wishlist -->
                    <a href="<?= url('wishlist') ?>" class="position-relative text-decoration-none icon-link" title="Lista de desejos">
                        <i class="bi bi-heart fs-5"></i>
                        <span class="badge-contador badge rounded-pill bg-danger" data-badge="wishlist"
                              <?= wishlist_qtd() === 0 ? 'hidden' : '' ?>><?= wishlist_qtd() ?></span>
                    </a>

                    <!-- Carrinho (abre offcanvas) -->
                    <button class="btn p-0 border-0 position-relative icon-link" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrinho" title="Carrinho">
                        <i class="bi bi-bag fs-5"></i>
                        <span class="badge-contador badge rounded-pill bg-danger" data-badge="carrinho"
                              <?= carrinho_qtd() === 0 ? 'hidden' : '' ?>><?= carrinho_qtd() ?></span>
                    </button>

                    <?php if (logado()): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?= e(user('nome')) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (eh_admin()): ?>
                                    <li><a class="dropdown-item" href="<?= url('admin') ?>"><i class="bi bi-speedometer2 me-2"></i>Painel</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= url('conta') ?>"><i class="bi bi-person me-2"></i>A minha conta</a></li>
                                <li>
                                    <form action="<?= url('logout') ?>" method="post" class="px-0">
                                        <?= csrf_field() ?>
                                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Terminar sessão</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= url('login') ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-person me-1"></i>Entrar</a>
                    <?php endif; ?>

                    <a href="<?= url('contacto') ?>" class="btn btn-sm btn-primary fw-semibold px-3">Fale Connosco</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ============ OFFCANVAS DO CARRINHO ============ -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCarrinho" aria-labelledby="tituloCarrinho">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="tituloCarrinho"><i class="bi bi-bag me-2"></i>O seu carrinho</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <!-- Preenchido no Módulo 6 (carrinho). Placeholder por agora. -->
            <p class="text-muted text-center my-auto">O seu carrinho está vazio.</p>
            <a href="<?= url('shop') ?>" class="btn btn-primary w-100 mt-3">Ver produtos</a>
        </div>
    </div>

    <!-- ============ NOTIFICAÇÕES (toasts) ============ -->
    <?php if ($flash !== []): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1200;">
            <?php foreach ($flash as $tipo => $mensagem): ?>
                <div class="toast align-items-center text-bg-<?= $tipo === 'erro' ? 'danger' : ($tipo === 'sucesso' ? 'success' : 'dark') ?> border-0 show" role="alert">
                    <div class="d-flex">
                        <div class="toast-body"><?= e($mensagem) ?></div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main>
