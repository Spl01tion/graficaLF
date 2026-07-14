<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Registo de erros em ficheiro (storage/logs/app.log).
 *
 * Serve sobretudo para nao perder a causa real de um erro quando, em
 * producao, mostramos ao utilizador apenas uma mensagem generica.
 */
final class Logger
{
    private function __construct()
    {
    }

    /** @param array<string, mixed> $contexto */
    public static function erro(string $mensagem, array $contexto = []): void
    {
        self::escrever('ERRO', $mensagem, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public static function aviso(string $mensagem, array $contexto = []): void
    {
        self::escrever('AVISO', $mensagem, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public static function info(string $mensagem, array $contexto = []): void
    {
        self::escrever('INFO', $mensagem, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    private static function escrever(string $nivel, string $mensagem, array $contexto): void
    {
        $pasta = dirname(__DIR__, 2) . '/storage/logs';

        if (! is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }

        $linha = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            $nivel,
            $mensagem,
            $contexto === [] ? '' : ' ' . json_encode($contexto, JSON_UNESCAPED_UNICODE)
        );

        // FILE_APPEND + LOCK_EX evita que dois pedidos em simultaneo
        // corrompam a mesma linha do ficheiro.
        file_put_contents($pasta . '/app.log', $linha, FILE_APPEND | LOCK_EX);
    }
}
