<?php

/**
 * footer.php — Rodapé comum + fecho do documento.
 * Carrega o Bootstrap JS e o script do projecto. Fecha <main>, <body>, <html>.
 */
?>
    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="footer text-white pt-5 pb-4 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-white mb-3" href="<?= url('home') ?>">
                        <span class="logo-badge">GL</span>
                        <span class="fs-5"><?= e(APP_NAME) ?></span>
                    </a>
                    <p class="text-white-50 mb-3">
                        Impressão de qualidade e design criativo em Maputo. Damos vida às suas ideias —
                        do cartão de visita ao roll-up banner.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-btn"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-btn"><i class="bi bi-whatsapp"></i></a>
                        <a href="#" class="social-btn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="fw-bold text-uppercase mb-3">Navegação</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?= url('home') ?>">Início</a></li>
                        <li><a href="<?= url('shop') ?>">Shop</a></li>
                        <li><a href="<?= url('servicos') ?>">Serviços</a></li>
                        <li><a href="<?= url('sobre') ?>">Sobre</a></li>
                        <li><a href="<?= url('contacto') ?>">Contacto</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-3">
                    <h6 class="fw-bold text-uppercase mb-3">Serviços</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?= url('servicos') ?>">Impressão geral</a></li>
                        <li><a href="<?= url('servicos') ?>">Banners &amp; Roll-ups</a></li>
                        <li><a href="<?= url('servicos') ?>">Capas de livros</a></li>
                        <li><a href="<?= url('servicos') ?>">Design gráfico</a></li>
                        <li><a href="<?= url('servicos') ?>">Branding</a></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="fw-bold text-uppercase mb-3">Contacto</h6>
                    <ul class="list-unstyled footer-links">
                        <li><i class="bi bi-geo-alt-fill me-2"></i>Av. Julius Nyerere, Maputo</li>
                        <li><i class="bi bi-telephone-fill me-2"></i><?= e(config_get('site_phone', '+258 84 000 0000')) ?></li>
                        <li><i class="bi bi-envelope-fill me-2"></i><?= e(config_get('site_email', 'geral@graficalifei.co.mz')) ?></li>
                        <li><i class="bi bi-clock-fill me-2"></i>Seg–Sex: 08h–17h</li>
                    </ul>
                </div>
            </div>

            <hr class="border-white-50 my-4">
            <div class="text-center small text-white-50">
                <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Todos os direitos reservados.</span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS bundle (inclui Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/app.js') ?>"></script>

    <?php limpar_erros(); // descarta erros/dados antigos após serem mostrados ?>
</body>
</html>
