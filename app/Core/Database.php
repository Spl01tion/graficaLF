<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Camada de acesso a base de dados (PDO + MySQL).
 *
 * Decisoes importantes:
 *
 *  1. LIGACAO UNICA (singleton). Abrimos UMA ligacao por pedido HTTP e
 *     reutilizamo-la. Abrir uma ligacao nova por cada consulta e um erro
 *     comum que esgota as ligacoes do MySQL sob carga.
 *
 *  2. PREPARED STATEMENTS REAIS. `ATTR_EMULATE_PREPARES => false` obriga o
 *     MySQL (e nao o PHP) a preparar a consulta. Os dados viajam separados
 *     do SQL, o que torna a injeccao de SQL impossivel — desde que NUNCA
 *     se concatene input do utilizador dentro da string SQL.
 *
 *  3. EXCEPCOES. `ERRMODE_EXCEPTION` faz o PDO lancar erro em vez de
 *     devolver `false` silenciosamente, que e como os bugs passam despercebidos.
 */
final class Database
{
    private static ?PDO $ligacao = null;

    /** Classe estatica: nao deve ser instanciada. */
    private function __construct()
    {
    }

    /**
     * Devolve a ligacao PDO, criando-a na primeira chamada.
     */
    public static function ligacao(): PDO
    {
        if (self::$ligacao instanceof PDO) {
            return self::$ligacao;
        }

        $cfg = Config::get('db');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['charset']
        );

        try {
            self::$ligacao = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                // Lanca excepcao em qualquer erro de SQL.
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Devolve arrays associativos (e nao arrays duplicados com indices numericos).
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Prepared statements nativos do MySQL — ver ponto 2 acima.
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Mantem os inteiros como inteiros em vez de strings.
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // Em producao nunca mostramos a mensagem original: pode conter
            // o utilizador, o host e ate a password da base de dados.
            if (Config::get('debug')) {
                throw new RuntimeException('Falha na ligacao a base de dados: ' . $e->getMessage(), 0, $e);
            }

            Logger::erro('Falha na ligacao a base de dados', ['erro' => $e->getMessage()]);
            exit('Servico temporariamente indisponivel. Tente novamente mais tarde.');
        }

        return self::$ligacao;
    }

    /**
     * Executa uma consulta preparada e devolve o statement.
     *
     * @param  array<string, mixed>  $dados  Valores a ligar aos marcadores (:nome).
     */
    public static function executar(string $sql, array $dados = []): PDOStatement
    {
        $stm = self::ligacao()->prepare($sql);
        $stm->execute($dados);

        return $stm;
    }

    /**
     * Devolve TODAS as linhas de uma consulta.
     *
     * @param  array<string, mixed>  $dados
     * @return array<int, array<string, mixed>>  Array vazio se nao houver resultados.
     */
    public static function todos(string $sql, array $dados = []): array
    {
        return self::executar($sql, $dados)->fetchAll();
    }

    /**
     * Devolve UMA linha (a primeira) ou null se nao existir.
     *
     * Nota: devolvemos `null` — e nao `false` — para que o codigo que chama
     * possa usar `?->` e `??` sem surpresas.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>|null
     */
    public static function primeiro(string $sql, array $dados = []): ?array
    {
        $linha = self::executar($sql, $dados)->fetch();

        return $linha === false ? null : $linha;
    }

    /**
     * Devolve um unico valor escalar (ex.: COUNT(*)).
     *
     * @param  array<string, mixed>  $dados
     */
    public static function valor(string $sql, array $dados = []): mixed
    {
        $valor = self::executar($sql, $dados)->fetchColumn();

        return $valor === false ? null : $valor;
    }

    /**
     * Insere uma linha e devolve o ID gerado.
     *
     * Os nomes das colunas vem do codigo (nunca do utilizador), por isso
     * podem ser interpolados; os VALORES vao sempre por marcadores.
     *
     * @param  array<string, mixed>  $dados  ['coluna' => valor, ...]
     */
    public static function inserir(string $tabela, array $dados): int
    {
        $colunas    = array_keys($dados);
        $marcadores = array_map(static fn (string $c): string => ':' . $c, $colunas);

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $tabela,
            implode('`, `', $colunas),
            implode(', ', $marcadores)
        );

        self::executar($sql, $dados);

        return (int) self::ligacao()->lastInsertId();
    }

    /**
     * Actualiza linhas que correspondam a condicao e devolve o numero de linhas afectadas.
     *
     * @param  array<string, mixed>  $dados      Colunas a alterar.
     * @param  array<string, mixed>  $condicoes  Colunas do WHERE (combinadas com AND).
     */
    public static function actualizar(string $tabela, array $dados, array $condicoes): int
    {
        $atribuicoes = [];
        $parametros  = [];

        foreach ($dados as $coluna => $valor) {
            $atribuicoes[]            = "`{$coluna}` = :set_{$coluna}";
            $parametros["set_{$coluna}"] = $valor;
        }

        $filtros = [];
        foreach ($condicoes as $coluna => $valor) {
            $filtros[]                 = "`{$coluna}` = :where_{$coluna}";
            $parametros["where_{$coluna}"] = $valor;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $tabela,
            implode(', ', $atribuicoes),
            implode(' AND ', $filtros)
        );

        return self::executar($sql, $parametros)->rowCount();
    }

    /**
     * Apaga linhas que correspondam a condicao e devolve o numero de linhas afectadas.
     *
     * @param  array<string, mixed>  $condicoes
     */
    public static function apagar(string $tabela, array $condicoes): int
    {
        $filtros    = [];
        $parametros = [];

        foreach ($condicoes as $coluna => $valor) {
            $filtros[]           = "`{$coluna}` = :{$coluna}";
            $parametros[$coluna] = $valor;
        }

        $sql = sprintf(
            'DELETE FROM `%s` WHERE %s',
            $tabela,
            implode(' AND ', $filtros)
        );

        return self::executar($sql, $parametros)->rowCount();
    }

    /**
     * Executa varias operacoes numa transaccao.
     *
     * Se o callback lancar excepcao, faz-se rollback e nada e gravado —
     * essencial no checkout, onde um pedido e os seus itens tem de ser
     * gravados por inteiro ou nao serem gravados de todo.
     */
    public static function transaccao(callable $callback): mixed
    {
        $pdo = self::ligacao();
        $pdo->beginTransaction();

        try {
            $resultado = $callback($pdo);
            $pdo->commit();

            return $resultado;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
