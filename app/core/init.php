<?php

/**
 * ============================================================
 *  init.php — Arranque do núcleo
 * ------------------------------------------------------------
 *  Carrega, por ordem, tudo o que a aplicação precisa. É o
 *  equivalente ao init.php do MaxaPark, mas com uma diferença:
 *  aqui carregamos o Composer e o .env (exigência do enunciado),
 *  em vez de escrever as credenciais à mão no código.
 * ============================================================
 */

declare(strict_types=1);

// Raiz do projecto (uma pasta acima de /app).
define('BASE_PATH', dirname(__DIR__, 2));

// 1) Autoloader do Composer — disponibiliza PHPMailer e phpdotenv.
require BASE_PATH . '/vendor/autoload.php';

// 2) Variáveis de ambiente (.env). O ficheiro é obrigatório: sem ele
//    não há credenciais de base de dados nem de email.
if (! is_file(BASE_PATH . '/.env')) {
    exit('Ficheiro .env não encontrado. Copie o .env.example para .env e preencha as credenciais.');
}
Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();

// 3) Funções auxiliares primeiro (definem env(), usada logo a seguir),
//    depois a configuração, as credenciais da base de dados e o email.
require __DIR__ . '/functions.php';
require __DIR__ . '/config.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/loja.php';

// 4) Fuso horário e localização (Moçambique / Português).
date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Maputo'));
setlocale(LC_ALL, 'pt_PT.UTF-8', 'pt_PT', 'Portuguese');

// 5) Relato de erros conforme o ambiente: tudo visível em desenvolvimento,
//    nada exposto ao utilizador em produção (os erros vão para o log).
if (env('APP_DEBUG', 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    // Em produção, uma excepção não tratada é registada e mostra uma
    // página amigável — nunca uma stack trace (que revela caminhos e SQL).
    set_exception_handler(static function (Throwable $e): void {
        registar_log('Excepção: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        if (is_file(BASE_PATH . '/app/pages/500.php')) {
            require BASE_PATH . '/app/pages/500.php';
        } else {
            echo 'Ocorreu um erro inesperado. Tente novamente mais tarde.';
        }
    });
}
