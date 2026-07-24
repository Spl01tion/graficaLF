<?php

/**
 * robots.php — robots.txt gerado dinamicamente (usa o URL base real).
 * Acessível em /robots.txt (ver rewrite no .htaccess).
 */

header('Content-Type: text/plain; charset=utf-8');
?>
User-agent: *
Allow: /
Disallow: /admin
Disallow: /conta
Disallow: /carrinho
Disallow: /fazer-pedido
Disallow: /login
Disallow: /registar

Sitemap: <?= url('sitemap.xml') . "\n" ?>
