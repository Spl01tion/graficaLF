<?php

/**
 * ============================================================
 *  functions.php — Funções auxiliares globais
 * ------------------------------------------------------------
 *  Este ficheiro é a "biblioteca" do projecto, no mesmo espírito
 *  do functions.php do MaxaPark: funções procedimentais usadas
 *  em todas as páginas.
 *
 *  Está organizado por secções:
 *    1. Ambiente (.env)
 *    2. Base de dados (PDO + prepared statements)
 *    3. Sessão segura
 *    4. Autenticação e permissões (RBAC)
 *    5. Proteção CSRF
 *    6. Escape / saída segura (anti-XSS)
 *    7. Mensagens flash e repovoamento de formulários
 *    8. Rate limiting
 *    9. Utilitários (slug, moeda, redireccionar, paginação)
 * ============================================================
 */

declare(strict_types=1);

// ==================================================================
//  1. AMBIENTE (.env)
// ==================================================================

/**
 * Lê uma variável do .env, com valor por omissão.
 * O phpdotenv preenche $_ENV; aqui tratamos "vazio" como "ausente".
 */
function env(string $chave, ?string $default = null): ?string
{
    $valor = $_ENV[$chave] ?? getenv($chave);

    if ($valor === false || $valor === null || $valor === '') {
        return $default;
    }

    return (string) $valor;
}


// ==================================================================
//  2. BASE DE DADOS (PDO + prepared statements)
// ==================================================================

/**
 * Devolve a ligação PDO, criando-a apenas na primeira chamada.
 *
 * Diferença face ao MaxaPark: lá abria-se uma ligação NOVA em cada
 * query() (esgota ligações do MySQL sob carga). Aqui usamos UMA
 * ligação partilhada por pedido (static $con).
 */
function conexao(): PDO
{
    static $con = null;

    if ($con instanceof PDO) {
        return $con;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DBHOST, DBPORT, DBNAME, DBCHARSET);

    try {
        $con = new PDO($dsn, DBUSER, DBPASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lança excepção em erro
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // arrays associativos
            PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statements REAIS (anti-SQLi)
        ]);
    } catch (PDOException $e) {
        if (env('APP_DEBUG', 'false') === 'true') {
            exit('Erro de ligação à base de dados: ' . $e->getMessage());
        }
        registar_log('Falha na ligação à BD: ' . $e->getMessage());
        exit('Serviço temporariamente indisponível. Tente novamente mais tarde.');
    }

    return $con;
}

/**
 * Executa uma consulta preparada e devolve TODAS as linhas.
 *
 * Mantém a assinatura do MaxaPark: query($sql, $data). Os valores
 * do utilizador vão SEMPRE por marcadores (:nome), nunca concatenados.
 *
 * @param  array<string,mixed>  $data
 * @return array<int,array<string,mixed>>|false  false se não houver resultados
 */
function query(string $sql, array $data = []): array|false
{
    $stm = conexao()->prepare($sql);
    $stm->execute($data);

    $result = $stm->fetchAll();

    return (is_array($result) && $result !== []) ? $result : false;
}

/**
 * Como query(), mas devolve apenas a PRIMEIRA linha (ou false).
 *
 * @param  array<string,mixed>  $data
 * @return array<string,mixed>|false
 */
function query_row(string $sql, array $data = []): array|false
{
    $result = query($sql, $data);

    return $result === false ? false : $result[0];
}

/**
 * Executa uma instrução de escrita (INSERT/UPDATE/DELETE) e devolve
 * o número de linhas afectadas.
 *
 * @param  array<string,mixed>  $data
 */
function execute(string $sql, array $data = []): int
{
    $stm = conexao()->prepare($sql);
    $stm->execute($data);

    return $stm->rowCount();
}

/**
 * Insere uma linha a partir de um array associativo e devolve o ID gerado.
 * Os nomes das colunas vêm do código (nunca do utilizador).
 *
 * @param  array<string,mixed>  $data
 */
function inserir(string $tabela, array $data): int
{
    $colunas    = array_keys($data);
    $marcadores = array_map(fn ($c) => ':' . $c, $colunas);

    $sql = sprintf(
        'INSERT INTO `%s` (`%s`) VALUES (%s)',
        $tabela,
        implode('`, `', $colunas),
        implode(', ', $marcadores)
    );

    $stm = conexao()->prepare($sql);
    $stm->execute($data);

    return (int) conexao()->lastInsertId();
}

/** Regista uma mensagem no ficheiro de log (storage/logs/app.log). */
function registar_log(string $mensagem): void
{
    $pasta = BASE_PATH . '/storage/logs';
    if (! is_dir($pasta)) {
        @mkdir($pasta, 0755, true);
    }

    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . PHP_EOL;
    @file_put_contents($pasta . '/app.log', $linha, FILE_APPEND | LOCK_EX);
}


// ==================================================================
//  3. SESSÃO SEGURA
// ==================================================================

/**
 * Arranca a sessão com cookies endurecidos (exigência do enunciado):
 *   - httponly : o JavaScript não lê o cookie (mitiga roubo por XSS)
 *   - samesite : Lax — o cookie não viaja em POSTs de outros sites (anti-CSRF)
 *   - secure   : só por HTTPS (activado automaticamente quando há TLS)
 */
function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $seguro = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $seguro,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');   // não aceita IDs de sessão inventados
    ini_set('session.use_only_cookies', '1');

    session_name('GRAFICALIFEI');
    session_start();
}


// ==================================================================
//  4. AUTENTICAÇÃO E PERMISSÕES (RBAC)
// ==================================================================

/**
 * Lê um dado do utilizador autenticado (ou o array inteiro se $key vazio).
 * Mantém a assinatura do MaxaPark.
 */
function user(string $key = ''): mixed
{
    if ($key === '') {
        return $_SESSION['USER'] ?? null;
    }

    return $_SESSION['USER'][$key] ?? '';
}

/**
 * Regista o utilizador na sessão após login bem-sucedido.
 * Regenera o ID da sessão ANTES (previne session fixation).
 *
 * @param  array<string,mixed>  $row
 */
function authenticate(array $row): void
{
    session_regenerate_id(true);
    // Nunca guardamos a password na sessão.
    unset($row['password']);
    $_SESSION['USER'] = $row;
}

/** Verdadeiro se existe utilizador autenticado. */
function logado(): bool
{
    return ! empty($_SESSION['USER']);
}

/** Verdadeiro se o utilizador autenticado é administrador. */
function eh_admin(): bool
{
    return logado() && (user('role') === 'admin');
}

/**
 * Página de destino após o login: o admin vai para o painel, o cliente
 * volta para onde tentava ir (url_pretendido) ou para a página inicial.
 */
function destino_pos_login(): string
{
    if (eh_admin()) {
        return 'admin';
    }

    $pretendido = $_SESSION['url_pretendido'] ?? '';
    unset($_SESSION['url_pretendido']);

    return $pretendido !== '' ? $pretendido : 'home';
}

/** Termina a sessão por completo (logout). */
function terminar_sessao(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

/** Middleware: exige sessão iniciada, senão redirecciona para /login. */
function controlo_login(): void
{
    if (! logado()) {
        flash('erro', 'Inicie sessão para continuar.');
        $_SESSION['url_pretendido'] = $_GET['url'] ?? '';
        redirect('login');
    }
}

/** Middleware: exige perfil de administrador (RBAC do painel). */
function controlo_admin(): void
{
    controlo_login();

    if (! eh_admin()) {
        registar_log('Acesso negado ao painel — utilizador #' . (user('id_user') ?: '?'));
        http_response_code(403);
        flash('erro', 'Não tem permissão para aceder a esta área.');
        redirect('home');
    }
}


// ==================================================================
//  5. PROTEÇÃO CSRF
// ==================================================================

/** Devolve o token CSRF da sessão, gerando-o na primeira utilização. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Campo escondido a incluir dentro de TODOS os <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/**
 * Valida o token recebido num POST. Usa hash_equals (comparação em tempo
 * constante) para não permitir adivinhar o token medindo o tempo de resposta.
 * Termina o pedido se o token for inválido.
 */
function csrf_verificar(): void
{
    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (! is_string($token) || $token === '' || ! hash_equals(csrf_token(), $token)) {
        registar_log('Token CSRF inválido — IP ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        http_response_code(419);
        flash('erro', 'A sua sessão expirou. Por favor, tente novamente.');
        redirect($_GET['url'] ?? 'home');
    }
}


// ==================================================================
//  6. ESCAPE / SAÍDA SEGURA (anti-XSS)
// ==================================================================

/**
 * Escapa texto para HTML. A defesa principal contra XSS.
 *
 * REGRA DO PROJECTO: tudo o que venha da base de dados ou do utilizador
 * é impresso com <?= e($valor) ?>. Sem excepções.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


// ==================================================================
//  7. MENSAGENS FLASH E REPOVOAMENTO DE FORMULÁRIOS
// ==================================================================

/** Guarda uma mensagem que sobrevive exactamente a um redireccionamento. */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['_flash'][$tipo] = $mensagem;
}

/** Lê e apaga todas as mensagens flash (por isso só aparecem uma vez). */
function get_flash(): array
{
    $mensagens = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $mensagens;
}

/** Guarda erros de validação + dados submetidos, para repor o formulário. */
function guardar_erros(array $errors, array $antigos = []): void
{
    $_SESSION['_errors'] = $errors;
    $_SESSION['_old']    = $antigos;
}

/** Erro de validação de um campo específico (ou null). */
function erro(string $campo): ?string
{
    return $_SESSION['_errors'][$campo] ?? null;
}

/** Valor anteriormente submetido num campo (repovoamento), já escapado. */
function old(string $campo, string $default = ''): string
{
    return e($_SESSION['_old'][$campo] ?? $default);
}

/** Limpa erros e dados antigos (chamado pelo rodapé após os mostrar). */
function limpar_erros(): void
{
    unset($_SESSION['_errors'], $_SESSION['_old']);
}


// ==================================================================
//  8. RATE LIMITING
// ==================================================================

/**
 * Limita a frequência de uma acção por IP (login, contacto, pedidos).
 *
 * Implementação simples baseada na sessão + registo temporal. Devolve
 * true se a acção é PERMITIDA, false se o limite foi excedido.
 *
 * @param  string  $chave    identificador da acção (ex.: 'login', 'contacto')
 * @param  int     $max      número máximo de tentativas na janela
 * @param  int     $janela   duração da janela em segundos
 */
function rate_limit(string $chave, int $max, int $janela): bool
{
    $agora    = time();
    $registos = $_SESSION['_rate'][$chave] ?? [];

    // Descarta as tentativas que já saíram da janela de tempo.
    $registos = array_filter($registos, fn ($t) => ($t + $janela) > $agora);

    if (count($registos) >= $max) {
        return false;
    }

    $registos[] = $agora;
    $_SESSION['_rate'][$chave] = array_values($registos);

    return true;
}

/** Segundos que faltam até poder repetir a acção (para a mensagem ao utilizador). */
function rate_limit_espera(string $chave, int $janela): int
{
    $registos = $_SESSION['_rate'][$chave] ?? [];
    if ($registos === []) {
        return 0;
    }

    return max(0, (min($registos) + $janela) - time());
}


// ==================================================================
//  9. UTILITÁRIOS (slug, moeda, redireccionar, imagens, paginação)
// ==================================================================

/**
 * Renderiza um parcial (app/pages/partials/{nome}.php) e devolve o HTML.
 * Usado para reaproveitar blocos (cards, offcanvas) em várias páginas e
 * também nas respostas AJAX.
 *
 * @param  array<string,mixed>  $vars
 */
function parcial(string $nome, array $vars = []): string
{
    $ficheiro = dirname(__DIR__) . '/pages/partials/' . $nome . '.php';
    if (! is_file($ficheiro)) {
        return '';
    }

    extract($vars, EXTR_SKIP);
    ob_start();
    require $ficheiro;

    return (string) ob_get_clean();
}

/** Redirecciona para uma página interna e termina o pedido. */
function redirect(string $page): never
{
    header('Location: ' . ROOT . '/' . ltrim($page, '/'));
    exit;
}

/** URL absoluto de uma página interna. */
function url(string $page = ''): string
{
    return ROOT . '/' . ltrim($page, '/');
}

/** URL de um ficheiro estático (assets/...). */
function asset(string $caminho): string
{
    return ROOT . '/assets/' . ltrim($caminho, '/');
}

/**
 * URL de uma imagem carregada pelo admin. Se o ficheiro não existir,
 * devolve um marcador para não partir a grelha de produtos.
 */
function upload_url(?string $ficheiro): string
{
    if ($ficheiro && is_file(UPLOADS_PATH . '/' . $ficheiro)) {
        return ROOT . '/uploads/' . $ficheiro;
    }

    return ROOT . '/assets/img/sem-imagem.svg';
}

/**
 * URL de uma imagem de produto. Aceita três formas:
 *   - URL completo (ex.: Unsplash nos seeds)  -> devolve tal como está
 *   - nome de ficheiro carregado pelo admin   -> resolve em /uploads
 *   - vazio                                    -> marcador "sem imagem"
 */
function imagem_url(?string $valor): string
{
    if ($valor === null || $valor === '') {
        return ROOT . '/assets/img/sem-imagem.svg';
    }

    if (str_starts_with($valor, 'http://') || str_starts_with($valor, 'https://')) {
        return $valor;
    }

    return upload_url($valor);
}

/**
 * Valida e guarda uma imagem enviada (upload do admin).
 *
 * Segurança do upload (exigência do enunciado):
 *   - verifica o código de erro do PHP
 *   - limita o tamanho (3 MB)
 *   - valida o MIME REAL do ficheiro (não confia na extensão enviada)
 *   - renomeia para um nome aleatório (evita colisões e nomes maliciosos)
 *   - a pasta /uploads tem execução de PHP desligada (.htaccess)
 *
 * @param  array<string,mixed>  $ficheiro  Entrada de $_FILES.
 * @return array{ok:bool, nome:?string, erro:?string}
 */
function upload_imagem(array $ficheiro, string $prefixo = 'img'): array
{
    $falha = static fn (string $m): array => ['ok' => false, 'nome' => null, 'erro' => $m];

    if (($ficheiro['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $falha('Falha no envio da imagem.');
    }

    if ($ficheiro['size'] > 3 * 1024 * 1024) {
        return $falha('A imagem não pode exceder 3 MB.');
    }

    // MIME real, lido do conteúdo do ficheiro (não do nome).
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($ficheiro['tmp_name']);

    $extensoes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (! isset($extensoes[$mime])) {
        return $falha('Formato inválido. Use JPG, PNG, WEBP ou GIF.');
    }

    if (! is_dir(UPLOADS_PATH)) {
        @mkdir(UPLOADS_PATH, 0755, true);
    }

    $nome = $prefixo . '_' . date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $extensoes[$mime];

    if (! move_uploaded_file($ficheiro['tmp_name'], UPLOADS_PATH . '/' . $nome)) {
        return $falha('Não foi possível guardar a imagem.');
    }

    return ['ok' => true, 'nome' => $nome, 'erro' => null];
}

/** Apaga uma imagem carregada (se existir e não for um URL externo). */
function apagar_upload(?string $nome): void
{
    if ($nome && ! str_starts_with($nome, 'http') && is_file(UPLOADS_PATH . '/' . $nome)) {
        @unlink(UPLOADS_PATH . '/' . $nome);
    }
}

/** Converte texto em slug para URL: "Cartões 350g" -> "cartoes-350g". */
function str_to_url(string $texto): string
{
    // O iconv//TRANSLIT depende da biblioteca do sistema: no Windows devolve
    // "Cart~oes" em vez de "Cartoes". Os acentos do português são traduzidos
    // aqui, para o slug ser igual em qualquer servidor.
    $texto = strtr($texto, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N',
    ]);

    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? '';

    return trim($texto, '-');
}

/** Formata um valor em Meticais: 1234.5 -> "1.234,50 MZN". */
function moeda(float|int|string $valor): string
{
    return number_format((float) $valor, 2, ',', '.') . ' ' . MOEDA;
}

/** Classe CSS "active" quando o URL actual corresponde ao caminho dado. */
function nav_activo(string $caminho): string
{
    $actual = strtolower(trim($_GET['url'] ?? 'home', '/'));
    $actual = $actual === '' ? 'home' : $actual;
    $alvo   = strtolower(trim($caminho, '/'));

    return str_starts_with($actual, $alvo) ? 'active' : '';
}

/** Número de itens no carrinho (badge da navbar). */
function carrinho_qtd(): int
{
    $carrinho = $_SESSION['carrinho'] ?? [];

    return array_sum(array_column($carrinho, 'quantidade'));
}

/** Número de itens na wishlist (badge da navbar). */
function wishlist_qtd(): int
{
    return count($_SESSION['wishlist'] ?? []);
}

/**
 * Variáveis de paginação, calculadas a partir do URL actual.
 * Mantém a mesma função do MaxaPark (usada pelo index.php).
 *
 * @return array<string,mixed>
 */
function get_pagination_vars(): array
{
    $page_number = (int) ($_GET['page'] ?? 1);
    $page_number = $page_number < 1 ? 1 : $page_number;

    $current_link = ROOT . '/' . ($_GET['url'] ?? 'home');

    $query_string = '';
    foreach ($_GET as $key => $value) {
        if ($key !== 'url') {
            $query_string .= '&' . $key . '=' . $value;
        }
    }

    if (! str_contains($query_string, 'page=')) {
        $query_string .= '&page=' . $page_number;
    }

    $query_string = trim($query_string, '&');
    $current_link .= '?' . $query_string;

    $current_link = preg_replace('/page=[^&]*/', 'page=' . $page_number, $current_link);
    $next_link    = preg_replace('/page=[^&]*/', 'page=' . ($page_number + 1), $current_link);
    $prev_number  = $page_number < 2 ? 1 : $page_number - 1;
    $prev_link    = preg_replace('/page=[^&]*/', 'page=' . $prev_number, $current_link);
    $first_link   = preg_replace('/page=[^&]*/', 'page=1', $current_link);

    return [
        'page_number' => $page_number,
        'current_link' => $current_link,
        'next_link'   => $next_link,
        'prev_link'   => $prev_link,
        'first_link'  => $first_link,
    ];
}

/**
 * Números a mostrar numa barra de paginação, sem os listar todos.
 * Devolve sempre a primeira e a última página, mais uma janela à volta
 * da página actual; os saltos são marcados com reticências.
 *
 * Ex.: pagina 12 de 63  ->  [1, '…', 10, 11, 12, 13, 14, '…', 63]
 *
 * @return list<int|string>
 */
function paginacao_numeros(int $pagina, int $totalPaginas, int $janela = 2): array
{
    if ($totalPaginas < 1) {
        return [];
    }

    $numeros = [1, $totalPaginas];
    for ($i = $pagina - $janela; $i <= $pagina + $janela; $i++) {
        if ($i >= 1 && $i <= $totalPaginas) {
            $numeros[] = $i;
        }
    }

    $numeros = array_unique($numeros);
    sort($numeros);

    $saida    = [];
    $anterior = 0;
    foreach ($numeros as $n) {
        // Um salto de duas ou mais páginas vira reticências; um salto de
        // uma só mostra o número que falta (fica mais limpo do que "…").
        if ($anterior > 0 && $n - $anterior === 2) {
            $saida[] = $anterior + 1;
        } elseif ($anterior > 0 && $n - $anterior > 2) {
            $saida[] = '…';
        }
        $saida[]  = $n;
        $anterior = $n;
    }

    return $saida;
}

/**
 * Lê uma configuração da tabela `settings` (com cache estática).
 * A tabela é criada no Módulo 1; até lá devolve o valor por omissão.
 */
function config_get(string $chave, ?string $default = null): ?string
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $rows = query('SELECT `key`, `value` FROM settings');
            if ($rows) {
                foreach ($rows as $r) {
                    $cache[$r['key']] = $r['value'];
                }
            }
        } catch (Throwable) {
            // Tabela ainda não existe (antes do Módulo 1) — ignora.
        }
    }

    return $cache[$chave] ?? $default;
}
