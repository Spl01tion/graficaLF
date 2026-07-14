<?php

/**
 * Configuracao da aplicacao.
 *
 * Este ficheiro NAO contem segredos: apenas le as variaveis do ambiente
 * (.env) e devolve-as num array. Assim, as credenciais vivem sempre num
 * unico sitio (.env, fora do controlo de versoes).
 */

declare(strict_types=1);

use App\Core\Env;

return [

    // --- Aplicacao ---
    'name'     => Env::get('APP_NAME', 'Gráfica Lifei'),
    'env'      => Env::get('APP_ENV', 'production'),
    'debug'    => Env::bool('APP_DEBUG', false),
    'url'      => rtrim(Env::get('APP_URL', 'http://localhost'), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Africa/Maputo'),
    'locale'   => Env::get('APP_LOCALE', 'pt'),
    'key'      => Env::get('APP_KEY', ''),

    // --- Base de dados ---
    'db' => [
        'host'    => Env::get('DB_HOST', '127.0.0.1'),
        'port'    => Env::int('DB_PORT', 3306),
        'name'    => Env::get('DB_NAME', 'grafica_lifei'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    ],

    // --- Email (PHPMailer) ---
    'mail' => [
        'host'       => Env::get('MAIL_HOST', 'localhost'),
        'port'       => Env::int('MAIL_PORT', 587),
        'username'   => Env::get('MAIL_USERNAME', ''),
        'password'   => Env::get('MAIL_PASSWORD', ''),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'from_email' => Env::get('MAIL_FROM_ADDRESS', 'geral@graficalifei.co.mz'),
        'from_name'  => Env::get('MAIL_FROM_NAME', 'Gráfica Lifei'),
        'contact_to' => Env::get('MAIL_CONTACT_TO', 'geral@graficalifei.co.mz'),
    ],

    // --- Limites de tentativas (rate limiting) ---
    'rate_limit' => [
        'login'   => Env::int('RATE_LIMIT_LOGIN', 5),
        'contact' => Env::int('RATE_LIMIT_CONTACT', 3),
        'window'  => Env::int('RATE_LIMIT_WINDOW', 900),
    ],
];
