<?php

/**
 * ============================================================
 *  conexao.php — Credenciais da base de dados
 * ------------------------------------------------------------
 *  Mesmo estilo do conexao.php do MaxaPark: escolhe as
 *  credenciais consoante o SERVER_NAME (localhost = XAMPP local,
 *  qualquer outro = hosting/InfinityFree) — mas os valores em si
 *  vêm sempre do .env (nunca escritos no código), para não haver
 *  segredos versionados no Git.
 *
 *  A ligação PDO propriamente dita é criada em functions.php
 *  (função conexao()), reutilizada em todas as consultas.
 * ============================================================
 */

declare(strict_types=1);

// Na linha de comandos (seeds, tarefas) não existe SERVER_NAME — assume-se local.
if (($_SERVER['SERVER_NAME'] ?? 'localhost') === 'localhost') {
    define('DBHOST', env('DB_HOST', 'localhost'));
    define('DBPORT', env('DB_PORT', '3306'));
    define('DBNAME', env('DB_NAME', 'graficalf_db'));
    define('DBUSER', env('DB_USER', 'root'));
    define('DBPASS', env('DB_PASS', ''));
} else {
    // Hosting (InfinityFree)
    define('DBHOST', env('DB_HOST', 'sql301.infinityfree.com'));
    define('DBPORT', env('DB_PORT', '3306'));
    define('DBNAME', env('DB_NAME', 'if0_41931075_graficalf_db'));
    define('DBUSER', env('DB_USER', 'if0_41931075'));
    define('DBPASS', env('DB_PASS', ''));
}

define('DBCHARSET', env('DB_CHARSET', 'utf8mb4'));
