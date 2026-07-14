<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

/**
 * Leitura das variaveis de ambiente (.env), via vlucas/phpdotenv.
 *
 * Porque nao usar $_ENV directamente? Porque o .env devolve sempre strings.
 * Esta classe converte para os tipos certos (bool, int) e centraliza os
 * valores por omissao, evitando "true" (string) a ser lido como verdadeiro
 * por engano.
 */
final class Env
{
    /** Evita carregar o .env mais do que uma vez por pedido. */
    private static bool $carregado = false;

    /**
     * Carrega o .env a partir da raiz do projecto.
     * O .env e obrigatorio: sem ele a aplicacao nao tem credenciais.
     */
    public static function load(string $caminhoRaiz): void
    {
        if (self::$carregado) {
            return;
        }

        if (! is_file($caminhoRaiz . '/.env')) {
            exit(
                'Ficheiro .env nao encontrado. '
                . 'Copie o .env.example para .env e preencha as credenciais.'
            );
        }

        Dotenv::createImmutable($caminhoRaiz)->load();
        self::$carregado = true;
    }

    /** Le uma variavel como string. */
    public static function get(string $chave, ?string $default = null): ?string
    {
        $valor = $_ENV[$chave] ?? null;

        // Uma variavel presente mas vazia conta como ausente.
        if ($valor === null || $valor === '') {
            return $default;
        }

        return $valor;
    }

    /** Le uma variavel como booleano ("true", "1", "yes" => true). */
    public static function bool(string $chave, bool $default = false): bool
    {
        $valor = self::get($chave);

        if ($valor === null) {
            return $default;
        }

        return in_array(strtolower($valor), ['true', '1', 'yes', 'on'], true);
    }

    /** Le uma variavel como inteiro. */
    public static function int(string $chave, int $default = 0): int
    {
        $valor = self::get($chave);

        return $valor === null ? $default : (int) $valor;
    }
}
