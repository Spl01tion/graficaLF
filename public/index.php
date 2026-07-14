<?php

/**
 * Recupera a URL da requisição GET, utilizando 'home' como padrão caso não seja fornecida.
 *
 * @param string|null $url A URL da requisição GET, ou null se não fornecida.
 *
 * @return string A URL recuperada, ou 'home' se não fornecida.
 */
session_start();

require __DIR__ . "/app/core/init.php";

$url = $_GET['url'] ?? 'home';
$url = strtolower($url);
$url = explode("/", $url);

$page_name = trim($url[0]);
$filename = __DIR__ . "/app/pages/" . $page_name . ".php";

$PAGE = get_pagination_vars();


if (file_exists($filename)) {
    require_once $filename;
} else {
    require_once __DIR__ . "/app/pages/404.php";
}

//Testes
//print_r($url);