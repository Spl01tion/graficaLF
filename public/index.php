<?php

/**
 * ============================================================
 *  Gráfica Lifei — Front Controller (ponto único de entrada)
 * ------------------------------------------------------------
 *  Segue a mesma estrutura do projecto MaxaPark:
 *  o .htaccess reescreve TODOS os pedidos para aqui, e este
 *  ficheiro decide qual a "página" a carregar a partir do URL.
 *
 *  Exemplos de roteamento:
 *    /                 -> app/pages/home.php
 *    /shop             -> app/pages/shop.php
 *    /admin/produtos   -> app/pages/admin.php   ($url[1] = "produtos")
 *    /shop/banner-x    -> app/pages/shop.php     ($url[1] = "banner-x")
 * ============================================================
 */

// Carrega Composer (phpdotenv, PHPMailer), .env, config, ligação PDO e funções.
require "../app/core/init.php";

// Arranca a sessão com cookies endurecidos (httponly, samesite, secure).
iniciar_sessao();

// ------------------------------------------------------------------
//  Determinar a página a partir do URL amigável (?url=...)
// ------------------------------------------------------------------
$url = $_GET['url'] ?? 'home';
$url = strtolower(trim($url, '/'));
$url = $url === '' ? 'home' : $url;
$url = explode('/', $url);

$page_name = trim($url[0]);

// Impede navegação para fora da pasta de páginas (../, etc.).
$page_name = preg_replace('/[^a-z0-9_-]/', '', $page_name);

$filename = "../app/pages/" . $page_name . ".php";

// Variáveis de paginação disponíveis em todas as páginas (grelha da loja, etc.).
$PAGE = get_pagination_vars();

if (is_file($filename)) {
    require_once $filename;
} else {
    http_response_code(404);
    require_once "../app/pages/404.php";
}
