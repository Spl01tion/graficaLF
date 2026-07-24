<?php

/**
 * ============================================================
 *  config.php — Constantes globais da aplicação
 * ------------------------------------------------------------
 *  Ao contrário do MaxaPark (que escrevia os valores à mão),
 *  aqui lemos do .env através da função env(). Assim as
 *  credenciais ficam num só sítio, fora do controlo de versões.
 * ============================================================
 */

declare(strict_types=1);

// URL base do site (sem barra final). Tudo o que é impresso nas vistas
// — links, assets, imagens — parte daqui.
define('ROOT', rtrim(env('APP_URL', 'http://localhost/grafica_lifei/public'), '/'));

// Nome da aplicação (título, emails, rodapé).
define('APP_NAME', env('APP_NAME', 'Gráfica Lifei'));

// Caminho físico da pasta de uploads (imagens de produtos).
define('UPLOADS_PATH', dirname(__DIR__, 2) . '/public/uploads');

// Moeda usada em todo o site.
define('MOEDA', 'MZN');

// Limites de tentativas (rate limiting) — lidos do .env com valores por omissão.
define('RATE_LIMIT_LOGIN',   (int) env('RATE_LIMIT_LOGIN', '5'));
define('RATE_LIMIT_CONTACT', (int) env('RATE_LIMIT_CONTACT', '3'));
define('RATE_LIMIT_ORDER',   (int) env('RATE_LIMIT_ORDER', '5'));
define('RATE_LIMIT_WINDOW',  (int) env('RATE_LIMIT_WINDOW', '900'));
