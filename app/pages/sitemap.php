<?php

/**
 * sitemap.php — Mapa do site (XML) gerado dinamicamente.
 * Acessível em /sitemap.xml (ver rewrite no .htaccess).
 */

header('Content-Type: application/xml; charset=utf-8');

$urls = [];

// Páginas estáticas.
foreach (['home' => '1.0', 'shop' => '0.9', 'servicos' => '0.7', 'sobre' => '0.6', 'contacto' => '0.6'] as $p => $prioridade) {
    $urls[] = ['loc' => url($p === 'home' ? '' : $p), 'prioridade' => $prioridade];
}

// Categorias.
foreach (query('SELECT slug FROM categories') ?: [] as $c) {
    $urls[] = ['loc' => url('shop?categoria=' . $c['slug']), 'prioridade' => '0.7'];
}

// Produtos activos.
foreach (query('SELECT slug, updated_at FROM products WHERE ativo = 1') ?: [] as $p) {
    $urls[] = ['loc' => url('shop/' . $p['slug']), 'prioridade' => '0.8', 'lastmod' => date('Y-m-d', strtotime($p['updated_at']))];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . e($u['loc']) . "</loc>\n";
    if (! empty($u['lastmod'])) {
        echo '    <lastmod>' . e($u['lastmod']) . "</lastmod>\n";
    }
    echo '    <priority>' . e($u['prioridade']) . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
