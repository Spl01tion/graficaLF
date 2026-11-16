<?php

/**
 * pagina-legal.php — Layout comum às páginas legais
 * (Termos & Condições, Política de Privacidade, Segurança).
 *
 * A página que inclui este ficheiro define antes:
 *   $titulo      (string) — título (SEO + cabeçalho)
 *   $descricao   (string) — meta description
 *   $subtitulo   (string) — texto por baixo do título no cabeçalho
 *   $atualizado  (string) — data da última actualização (ex.: '16 de Novembro de 2026')
 *   $seccoes     (array)  — [['id' => 'ancora', 'titulo' => '...', 'html' => '...'], ...]
 *                            O 'html' é conteúdo estático escrito por nós (não vem do utilizador).
 */

$paginasLegais = [
    'termos'      => ['icone' => 'bi-file-earmark-text', 'nome' => 'Termos & Condições'],
    'privacidade' => ['icone' => 'bi-shield-lock',       'nome' => 'Política de Privacidade'],
    'seguranca'   => ['icone' => 'bi-shield-check',      'nome' => 'Segurança'],
];
$paginaActual = $page_name ?? '';

require __DIR__ . '/header.php';
?>

<section class="page-header text-white py-5">
    <div class="container py-4 text-center">
        <h1 class="fw-bold mb-2"><?= e($titulo) ?></h1>
        <p class="text-white-50 mb-0"><?= e($subtitulo) ?></p>
    </div>
</section>

<section class="py-5">
    <div class="container py-2">
        <div class="row g-5">
            <!-- Índice + navegação entre páginas legais -->
            <aside class="col-lg-3">
                <div class="legal-indice">
                    <h2 class="h6 fw-bold text-uppercase text-muted mb-3">Nesta página</h2>
                    <ol class="list-unstyled legal-indice__lista mb-4">
                        <?php foreach ($seccoes as $i => $s): ?>
                            <li><a href="#<?= e($s['id']) ?>"><span><?= $i + 1 ?>.</span> <?= e($s['titulo']) ?></a></li>
                        <?php endforeach; ?>
                    </ol>

                    <h2 class="h6 fw-bold text-uppercase text-muted mb-3">Informação legal</h2>
                    <div class="list-group legal-nav">
                        <?php foreach ($paginasLegais as $slug => $p): ?>
                            <a href="<?= url($slug) ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2<?= $slug === $paginaActual ? ' active' : '' ?>"
                                <?= $slug === $paginaActual ? 'aria-current="page"' : '' ?>>
                                <i class="bi <?= $p['icone'] ?>"></i> <?= e($p['nome']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <!-- Conteúdo -->
            <article class="col-lg-9 legal-conteudo">
                <p class="small text-muted mb-4">
                    <i class="bi bi-calendar3 me-1"></i> Última actualização: <?= e($atualizado) ?>
                </p>

                <?php foreach ($seccoes as $i => $s): ?>
                    <section id="<?= e($s['id']) ?>" class="mb-5">
                        <h2 class="h4 fw-bold mb-3"><?= $i + 1 ?>. <?= e($s['titulo']) ?></h2>
                        <?= $s['html'] ?>
                    </section>
                <?php endforeach; ?>

                <div class="card border-0 bg-light">
                    <div class="card-body p-4 d-md-flex align-items-center justify-content-between gap-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Tem alguma dúvida?</h2>
                            <p class="text-muted mb-md-0">
                                Fale connosco através de
                                <a href="mailto:<?= e(config_get('site_email', 'geral@graficalifei.co.mz')) ?>"><?= e(config_get('site_email', 'geral@graficalifei.co.mz')) ?></a>
                                ou <?= e(config_get('site_phone', '+258 84 000 0000')) ?>.
                            </p>
                        </div>
                        <a href="<?= url('contacto') ?>" class="btn btn-primary flex-shrink-0">Contactar</a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
