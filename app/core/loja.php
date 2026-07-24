<?php

/**
 * ============================================================
 *  loja.php — Lógica de carrinho, wishlist, cupões e pedidos
 * ------------------------------------------------------------
 *  Funções da loja usadas pelas páginas e endpoints (Módulos 5 e 6).
 *
 *  O carrinho vive na sessão. Estrutura de cada linha:
 *    $_SESSION['carrinho'][chave] = [
 *        'id_product' => int, 'nome' => string, 'preco_unit' => float,
 *        'quantidade' => int, 'opcoes' => string, 'imagem' => string
 *    ]
 *  A "chave" = md5(id_product + opções), para que o mesmo produto com as
 *  mesmas opções se agregue numa só linha.
 * ============================================================
 */

declare(strict_types=1);

// ------------------------------------------------------------------
//  CARRINHO
// ------------------------------------------------------------------

/** Gera a chave única de uma linha do carrinho (produto + opções). */
function carrinho_chave(int $idProduto, string $opcoes): string
{
    return md5($idProduto . '|' . $opcoes);
}

/**
 * Adiciona um produto ao carrinho (ou soma à quantidade existente).
 * O preço é sempre recalculado no servidor a partir da BD — nunca se
 * confia num preço vindo do formulário.
 *
 * @param  array<int,int>  $idsOpcoes  ids das opções escolhidas
 */
function carrinho_adicionar(int $idProduto, int $quantidade, array $idsOpcoes = []): bool
{
    $produto = query_row('SELECT * FROM products WHERE id_product = :id AND ativo = 1', ['id' => $idProduto]);
    if (! $produto) {
        return false;
    }

    $quantidade = max(1, $quantidade);
    $preco = (float) ($produto['preco_promo'] ?? null ?: $produto['preco']);

    // Soma os ajustes das opções escolhidas e monta o texto descritivo.
    $descricaoOpcoes = [];
    if ($idsOpcoes !== []) {
        $marcadores = implode(',', array_fill(0, count($idsOpcoes), '?'));
        $opcoes = query(
            "SELECT * FROM product_options WHERE id_product = ? AND id_option IN ($marcadores) ORDER BY ordem",
            array_merge([$idProduto], $idsOpcoes)
        );
        if ($opcoes) {
            foreach ($opcoes as $o) {
                $preco += (float) $o['ajuste_preco'];
                $descricaoOpcoes[] = $o['grupo'] . ': ' . $o['valor'];
            }
        }
    }

    $opcoesTexto = implode('; ', $descricaoOpcoes);
    $chave = carrinho_chave($idProduto, $opcoesTexto);

    $imagem = query_row(
        'SELECT image FROM product_images WHERE id_product = :id ORDER BY principal DESC, ordem LIMIT 1',
        ['id' => $idProduto]
    );

    if (isset($_SESSION['carrinho'][$chave])) {
        $_SESSION['carrinho'][$chave]['quantidade'] += $quantidade;
    } else {
        $_SESSION['carrinho'][$chave] = [
            'id_product' => $idProduto,
            'nome'       => $produto['nome'],
            'preco_unit' => $preco,
            'quantidade' => $quantidade,
            'opcoes'     => $opcoesTexto,
            'imagem'     => $imagem['image'] ?? '',
        ];
    }

    return true;
}

/** Actualiza a quantidade de uma linha (remove se <= 0). */
function carrinho_actualizar(string $chave, int $quantidade): void
{
    if (! isset($_SESSION['carrinho'][$chave])) {
        return;
    }

    if ($quantidade <= 0) {
        unset($_SESSION['carrinho'][$chave]);
    } else {
        $_SESSION['carrinho'][$chave]['quantidade'] = min($quantidade, 9999);
    }
}

/** Remove uma linha do carrinho. */
function carrinho_remover(string $chave): void
{
    unset($_SESSION['carrinho'][$chave]);
}

/** Esvazia o carrinho. */
function carrinho_limpar(): void
{
    unset($_SESSION['carrinho'], $_SESSION['cupao']);
}

/**
 * Devolve as linhas do carrinho com o subtotal de cada uma calculado.
 *
 * @return array<string,array<string,mixed>>
 */
function carrinho_itens(): array
{
    $itens = $_SESSION['carrinho'] ?? [];
    foreach ($itens as $chave => &$item) {
        $item['chave']    = $chave;
        $item['subtotal'] = $item['preco_unit'] * $item['quantidade'];
    }

    return $itens;
}

/** Soma total do carrinho (sem desconto). */
function carrinho_subtotal(): float
{
    $total = 0.0;
    foreach ($_SESSION['carrinho'] ?? [] as $item) {
        $total += $item['preco_unit'] * $item['quantidade'];
    }

    return $total;
}

// ------------------------------------------------------------------
//  WISHLIST (lista de desejos)
// ------------------------------------------------------------------

/**
 * Alterna um produto na wishlist. Devolve true se ficou adicionado.
 * Persiste na BD para utilizadores autenticados; caso contrário, na sessão.
 */
function wishlist_toggle(int $idProduto): bool
{
    $lista = $_SESSION['wishlist'] ?? [];

    if (in_array($idProduto, $lista, true)) {
        $lista = array_values(array_diff($lista, [$idProduto]));
        $adicionado = false;
        if (logado()) {
            execute('DELETE FROM wishlists WHERE id_user = :u AND id_product = :p',
                ['u' => user('id_user'), 'p' => $idProduto]);
        }
    } else {
        $lista[] = $idProduto;
        $adicionado = true;
        if (logado()) {
            // INSERT IGNORE para não duplicar (índice único user+product).
            execute('INSERT IGNORE INTO wishlists (id_user, id_product) VALUES (:u, :p)',
                ['u' => user('id_user'), 'p' => $idProduto]);
        }
    }

    $_SESSION['wishlist'] = $lista;

    return $adicionado;
}

/** Verifica se um produto está na wishlist. */
function wishlist_tem(int $idProduto): bool
{
    return in_array($idProduto, $_SESSION['wishlist'] ?? [], true);
}

/**
 * Carrega da BD a wishlist do utilizador autenticado para a sessão.
 * Chamado no login para reunir o que foi guardado antes.
 */
function wishlist_carregar_da_bd(): void
{
    if (! logado()) {
        return;
    }

    $rows = query('SELECT id_product FROM wishlists WHERE id_user = :u', ['u' => user('id_user')]);
    if ($rows) {
        $_SESSION['wishlist'] = array_map(static fn ($r) => (int) $r['id_product'], $rows);
    }
}

/** @return array<int,array<string,mixed>> Produtos da wishlist. */
function wishlist_produtos(): array
{
    $ids = $_SESSION['wishlist'] ?? [];
    if ($ids === []) {
        return [];
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    return query(
        "SELECT p.*, (SELECT image FROM product_images i WHERE i.id_product = p.id_product
                      ORDER BY principal DESC, ordem LIMIT 1) AS imagem
         FROM products p WHERE p.id_product IN ($marcadores) AND p.ativo = 1",
        $ids
    ) ?: [];
}

// ------------------------------------------------------------------
//  CUPÕES
// ------------------------------------------------------------------

/**
 * Valida um cupão face a um subtotal. Devolve o desconto calculado.
 *
 * @return array{ok:bool, msg:string, desconto:float, coupon:?array<string,mixed>}
 */
function cupao_validar(string $codigo, float $subtotal): array
{
    $falha = static fn (string $m): array => ['ok' => false, 'msg' => $m, 'desconto' => 0.0, 'coupon' => null];

    $codigo = strtoupper(trim($codigo));
    if ($codigo === '') {
        return $falha('Indique um código de cupão.');
    }

    $cp = query_row('SELECT * FROM coupons WHERE codigo = :c AND ativo = 1 LIMIT 1', ['c' => $codigo]);
    if (! $cp) {
        return $falha('Cupão inválido.');
    }

    $hoje = date('Y-m-d');
    if (! empty($cp['inicia_em']) && $cp['inicia_em'] > $hoje) {
        return $falha('Este cupão ainda não está activo.');
    }
    if (! empty($cp['expira_em']) && $cp['expira_em'] < $hoje) {
        return $falha('Este cupão expirou.');
    }
    if ($cp['limite_uso'] !== null && (int) $cp['usado'] >= (int) $cp['limite_uso']) {
        return $falha('Este cupão atingiu o limite de utilizações.');
    }
    if ($subtotal < (float) $cp['minimo']) {
        return $falha('Pedido mínimo de ' . moeda($cp['minimo']) . ' para usar este cupão.');
    }

    $desconto = $cp['tipo'] === 'percentual'
        ? $subtotal * ((float) $cp['valor'] / 100)
        : min((float) $cp['valor'], $subtotal);

    return [
        'ok'       => true,
        'msg'      => 'Cupão aplicado: -' . moeda($desconto),
        'desconto' => round($desconto, 2),
        'coupon'   => $cp,
    ];
}

/** Desconto actualmente aplicado (cupão guardado na sessão). */
function carrinho_desconto(): float
{
    $cupao = $_SESSION['cupao'] ?? null;
    if (! $cupao) {
        return 0.0;
    }

    $res = cupao_validar($cupao['codigo'], carrinho_subtotal());

    return $res['ok'] ? $res['desconto'] : 0.0;
}

/** Total do carrinho já com o desconto do cupão. */
function carrinho_total(): float
{
    return max(0, carrinho_subtotal() - carrinho_desconto());
}

// ------------------------------------------------------------------
//  PEDIDOS
// ------------------------------------------------------------------

/** Gera a próxima referência legível de pedido (ex.: GL-2026-0001). */
function pedido_referencia(int $idOrder): string
{
    return 'GL-' . date('Y') . '-' . str_pad((string) $idOrder, 4, '0', STR_PAD_LEFT);
}

/** Etiqueta legível de um estado de pedido. */
function estado_pedido(string $status): string
{
    return [
        'pendente'    => 'Pendente',
        'confirmado'  => 'Confirmado',
        'em_producao' => 'Em produção',
        'pronto'      => 'Pronto',
        'entregue'    => 'Entregue',
        'cancelado'   => 'Cancelado',
    ][$status] ?? ucfirst($status);
}

/** Classe de cor (Bootstrap) para o estado de um pedido. */
function estado_cor(string $status): string
{
    return [
        'pendente'    => 'warning',
        'confirmado'  => 'info',
        'em_producao' => 'primary',
        'pronto'      => 'secondary',
        'entregue'    => 'success',
        'cancelado'   => 'danger',
    ][$status] ?? 'secondary';
}
