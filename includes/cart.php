<?php
declare(strict_types=1);

/**
 * includes/cart.php
 * Helpers do carrinho em $_SESSION.
 * Estrutura: $_SESSION['carrinho'] = [ produto_id => quantidade, ... ]
 */

function cart_init(): void
{
    if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
}

/** Contagem de unidades no carrinho (para o badge do header). */
function cart_count(): int
{
    cart_init();
    $n = 0;
    foreach ($_SESSION['carrinho'] as $q) {
        $n += (int)$q;
    }
    return $n;
}

/**
 * Adiciona (ou incrementa) um produto no carrinho.
 * Retorna ['ok'=>bool, 'msg'=>string, 'limited'=>bool]
 */
function cart_add(int $produto_id, int $qtd, PDO $pdo): array
{
    if ($produto_id < 1) {
        return ['ok' => false, 'msg' => 'Produto inválido.', 'limited' => false];
    }
    if ($qtd < 1) {
        return ['ok' => false, 'msg' => 'Quantidade inválida.', 'limited' => false];
    }

    $stmt = $pdo->prepare('SELECT id, nome, estoque, ativo FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $produto_id]);
    $p = $stmt->fetch();

    if (!$p) {
        return ['ok' => false, 'msg' => 'Produto não encontrado.', 'limited' => false];
    }
    if (!(int)$p['ativo']) {
        return ['ok' => false, 'msg' => 'Produto indisponível.', 'limited' => false];
    }
    if ((int)$p['estoque'] <= 0) {
        return ['ok' => false, 'msg' => 'Produto esgotado.', 'limited' => false];
    }

    cart_init();
    $atual = (int)($_SESSION['carrinho'][$produto_id] ?? 0);
    $novo  = $atual + $qtd;
    $max   = (int)$p['estoque'];

    if ($novo > $max) {
        $added = $max - $atual;
        if ($added <= 0) {
            return [
                'ok'      => false,
                'msg'     => "Você já tem o máximo disponível em estoque deste item ({$max}).",
                'limited' => true,
            ];
        }
        $_SESSION['carrinho'][$produto_id] = $max;
        return [
            'ok'      => true,
            'msg'     => "Adicionado até o limite do estoque ({$max}).",
            'limited' => true,
        ];
    }

    $_SESSION['carrinho'][$produto_id] = $novo;
    return ['ok' => true, 'msg' => 'Produto adicionado ao carrinho.', 'limited' => false];
}

/**
 * Define a quantidade exata de um item (0 = remove).
 * Retorna ['ok'=>bool, 'msg'=>string, 'limited'=>bool]
 */
function cart_update(int $produto_id, int $qtd, PDO $pdo): array
{
    if ($produto_id < 1) {
        return ['ok' => false, 'msg' => 'Produto inválido.', 'limited' => false];
    }
    cart_init();
    if (!isset($_SESSION['carrinho'][$produto_id])) {
        return ['ok' => false, 'msg' => 'Item não está no carrinho.', 'limited' => false];
    }
    if ($qtd <= 0) {
        unset($_SESSION['carrinho'][$produto_id]);
        return ['ok' => true, 'msg' => 'Item removido do carrinho.', 'limited' => false];
    }

    $stmt = $pdo->prepare('SELECT estoque, ativo FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $produto_id]);
    $p = $stmt->fetch();

    if (!$p || !(int)$p['ativo']) {
        unset($_SESSION['carrinho'][$produto_id]);
        return ['ok' => false, 'msg' => 'Produto indisponível — removido do carrinho.', 'limited' => false];
    }
    $max = (int)$p['estoque'];
    if ($max <= 0) {
        unset($_SESSION['carrinho'][$produto_id]);
        return ['ok' => false, 'msg' => 'Produto esgotado — removido do carrinho.', 'limited' => false];
    }
    if ($qtd > $max) {
        $_SESSION['carrinho'][$produto_id] = $max;
        return ['ok' => true, 'msg' => "Quantidade ajustada ao estoque disponível ({$max}).", 'limited' => true];
    }

    $_SESSION['carrinho'][$produto_id] = $qtd;
    return ['ok' => true, 'msg' => 'Quantidade atualizada.', 'limited' => false];
}

/** Remove um item do carrinho. */
function cart_remove(int $produto_id): array
{
    cart_init();
    if (isset($_SESSION['carrinho'][$produto_id])) {
        unset($_SESSION['carrinho'][$produto_id]);
        return ['ok' => true, 'msg' => 'Item removido do carrinho.'];
    }
    return ['ok' => false, 'msg' => 'Item não encontrado.'];
}

/** Esvazia o carrinho. */
function cart_clear(): void
{
    $_SESSION['carrinho'] = [];
}

/**
 * Hidrata o carrinho com dados atuais do banco.
 */
function cart_get_detalhado(PDO $pdo): array
{
    cart_init();
    $raw = $_SESSION['carrinho'];

    if (empty($raw)) {
        return ['itens' => [], 'subtotal' => 0.0, 'total' => 0.0, 'count' => 0, 'avisos' => []];
    }

    $ids = array_values(array_filter(array_map('intval', array_keys($raw)), fn($n) => $n > 0));
    if (empty($ids)) {
        $_SESSION['carrinho'] = [];
        return ['itens' => [], 'subtotal' => 0.0, 'total' => 0.0, 'count' => 0, 'avisos' => []];
    }

    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, p.ativo,
                c.nome AS categoria
         FROM produtos p
         INNER JOIN categorias c ON c.id = p.categoria_id
         WHERE p.id IN ($ph)"
    );
    $stmt->execute($ids);

    $produtos = [];
    foreach ($stmt->fetchAll() as $row) {
        $produtos[(int)$row['id']] = $row;
    }

    $itens    = [];
    $subtotal = 0.0;
    $count    = 0;
    $avisos   = [];

    foreach ($raw as $id => $qtd) {
        $id  = (int)$id;
        $qtd = (int)$qtd;

        if (!isset($produtos[$id])) {
            unset($_SESSION['carrinho'][$id]);
            $avisos[] = 'Um item do carrinho não está mais disponível e foi removido.';
            continue;
        }
        $p = $produtos[$id];

        if (!(int)$p['ativo']) {
            unset($_SESSION['carrinho'][$id]);
            $avisos[] = "O produto \"{$p['nome']}\" foi desativado e saiu do carrinho.";
            continue;
        }
        if ((int)$p['estoque'] <= 0) {
            unset($_SESSION['carrinho'][$id]);
            $avisos[] = "O produto \"{$p['nome']}\" está esgotado e saiu do carrinho.";
            continue;
        }
        if ($qtd < 1) {
            unset($_SESSION['carrinho'][$id]);
            continue;
        }
        if ($qtd > (int)$p['estoque']) {
            $qtd = (int)$p['estoque'];
            $_SESSION['carrinho'][$id] = $qtd;
            $avisos[] = "Quantidade de \"{$p['nome']}\" ajustada ao estoque disponível ({$qtd}).";
        }

        $preco = (float)$p['preco'];
        $sub   = $preco * $qtd;

        $itens[] = [
            'id'             => $id,
            'nome'           => $p['nome'],
            'categoria'      => $p['categoria'],
            'imagem'         => $p['imagem'],
            'preco_unitario' => $preco,
            'quantidade'     => $qtd,
            'estoque'        => (int)$p['estoque'],
            'subtotal'       => $sub,
        ];
        $subtotal += $sub;
        $count    += $qtd;
    }

    return [
        'itens'    => $itens,
        'subtotal' => $subtotal,
        'total'    => $subtotal,
        'count'    => $count,
        'avisos'   => array_values(array_unique($avisos)),
    ];
}