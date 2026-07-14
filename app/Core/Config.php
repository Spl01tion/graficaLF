<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Acesso as definicoes de configuracao (config/app.php).
 *
 * Suporta notacao por pontos: Config::get('db.host'), Config::get('mail.port').
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $itens = [];

    private function __construct()
    {
    }

    /** Carrega o array devolvido por config/app.php. */
    public static function load(string $ficheiro): void
    {
        self::$itens = require $ficheiro;
    }

    /**
     * Le uma definicao. Aceita 'db.host' para descer no array.
     */
    public static function get(string $chave, mixed $default = null): mixed
    {
        $valor = self::$itens;

        foreach (explode('.', $chave) as $segmento) {
            if (! is_array($valor) || ! array_key_exists($segmento, $valor)) {
                return $default;
            }

            $valor = $valor[$segmento];
        }

        return $valor;
    }
}
