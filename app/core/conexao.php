<?php

/**
 * ============================================================
 *  conexao.php — Credenciais da base de dados
 * ------------------------------------------------------------
 *  Mantém o mesmo papel do conexao.php do MaxaPark (definir as
 *  constantes DBHOST/DBNAME/DBUSER/DBPASS), mas lê-as do .env
 *  em vez de as escrever no código.
 *
 *  A ligação PDO propriamente dita é criada em functions.php
 *  (função conexao()), reutilizada em todas as consultas.
 * ============================================================
 */

declare(strict_types=1);

define('DBHOST', env('DB_HOST', '127.0.0.1'));
define('DBPORT', env('DB_PORT', '3306'));
define('DBNAME', env('DB_NAME', 'grafica_lifei'));
define('DBUSER', env('DB_USER', 'root'));
define('DBPASS', env('DB_PASS', ''));
define('DBCHARSET', env('DB_CHARSET', 'utf8mb4'));
