<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestao da sessao com cookies endurecidos e proteccao CSRF.
 *
 * Cookie de sessao seguro (requisito do enunciado):
 *   - httponly  => o JavaScript nao consegue ler o cookie (mitiga roubo por XSS)
 *   - samesite  => Lax: o cookie nao viaja em pedidos POST vindos de outros
 *                  sites, o que ja trava boa parte dos ataques CSRF
 *   - secure    => so envia por HTTPS (activado automaticamente quando ha TLS)
 *
 * O token CSRF vive aqui porque esta ligado ao ciclo de vida da sessao.
 */
final class Session
{
    private const CHAVE_CSRF     = '_token_csrf';
    private const CHAVE_FLASH    = '_flash';
    private const CHAVE_UTILIZADOR = '_utilizador';

    private function __construct()
    {
    }

    /** Arranca a sessao com opcoes seguras. Chamado uma vez, no bootstrap. */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Deteta HTTPS (directo ou atras de um proxy/load balancer).
        $seguro = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,          // expira ao fechar o browser
            'path'     => '/',
            'httponly' => true,
            'secure'   => $seguro,
            'samesite' => 'Lax',
        ]);

        // Nao aceitar IDs de sessao vindos do URL (previne session fixation).
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_name('GRAFICALIFEI_SESSAO');
        session_start();
    }

    /**
     * Regenera o ID da sessao mantendo os dados.
     *
     * Obrigatorio no login e no logout: se um atacante conseguiu fixar um ID
     * de sessao antes da autenticacao, esse ID deixa de servir depois dela.
     */
    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $chave, mixed $default = null): mixed
    {
        return $_SESSION[$chave] ?? $default;
    }

    public static function set(string $chave, mixed $valor): void
    {
        $_SESSION[$chave] = $valor;
    }

    public static function tem(string $chave): bool
    {
        return isset($_SESSION[$chave]);
    }

    public static function remover(string $chave): void
    {
        unset($_SESSION[$chave]);
    }

    /** Destroi a sessao por completo (logout). */
    public static function destruir(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }

    // ------------------------------------------------------------------
    //  Utilizador autenticado
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $utilizador */
    public static function autenticar(array $utilizador): void
    {
        // Regenerar ANTES de gravar os dados do utilizador na sessao.
        self::regenerar();
        self::set(self::CHAVE_UTILIZADOR, $utilizador);
    }

    /** @return array<string, mixed>|null */
    public static function utilizador(): ?array
    {
        return self::get(self::CHAVE_UTILIZADOR);
    }

    public static function autenticado(): bool
    {
        return self::utilizador() !== null;
    }

    public static function ehAdmin(): bool
    {
        return (self::utilizador()['role'] ?? null) === 'admin';
    }

    // ------------------------------------------------------------------
    //  Proteccao CSRF
    // ------------------------------------------------------------------

    /**
     * Devolve o token CSRF da sessao, gerando-o na primeira utilizacao.
     * O mesmo token serve para toda a sessao (padrao "synchronizer token").
     */
    public static function tokenCsrf(): string
    {
        if (! self::tem(self::CHAVE_CSRF)) {
            self::set(self::CHAVE_CSRF, bin2hex(random_bytes(32)));
        }

        return (string) self::get(self::CHAVE_CSRF);
    }

    /**
     * Valida o token recebido num formulario.
     *
     * `hash_equals` compara em tempo constante — uma comparacao normal (===)
     * termina no primeiro caracter diferente e, medindo o tempo de resposta,
     * um atacante conseguiria adivinhar o token caracter a caracter.
     */
    public static function validarCsrf(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals(self::tokenCsrf(), $token);
    }

    // ------------------------------------------------------------------
    //  Mensagens flash (sobrevivem exactamente a um redireccionamento)
    // ------------------------------------------------------------------

    public static function flash(string $tipo, string $mensagem): void
    {
        $_SESSION[self::CHAVE_FLASH][$tipo] = $mensagem;
    }

    /** Le e apaga a mensagem — por isso e que so aparece uma vez. */
    public static function lerFlash(string $tipo): ?string
    {
        $mensagem = $_SESSION[self::CHAVE_FLASH][$tipo] ?? null;
        unset($_SESSION[self::CHAVE_FLASH][$tipo]);

        return $mensagem;
    }

    /** @return array<string, string> */
    public static function todasFlash(): array
    {
        $mensagens = $_SESSION[self::CHAVE_FLASH] ?? [];
        unset($_SESSION[self::CHAVE_FLASH]);

        return $mensagens;
    }
}
