<?php
declare(strict_types=1);

/**
 * checkout.php — Finalização do pedido (requer login).
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart.php';

require_login();

$pdo      = db();
$usuario  = usuario_atual();
$formas_validas = ['pix', 'boleto', 'cartao'];

if (is_post()) {
    csrf_verify();

    $token_sess = $_SESSION['checkout_token'] ?? '';
    $token_post = $_POST['checkout_token'] ?? '';
    if (!is_string($token_sess) || !is_string($token_post)
        || $token_sess === '' || !hash_equals($token_sess, $token_post)) {
        flash_set('erro', 'Sessão de checkout expirada. Revise o pedido e envie novamente.');
        redirect('checkout.php');
    }
    unset($_SESSION['checkout_token']);

    $forma = $_POST['forma_pagamento'] ?? '';
    if (!is_string($forma) || !in_array($forma, $formas_validas, true)) {
        flash_set('erro', 'Selecione uma forma de pagamento válida.');
        redirect('checkout.php');
    }

    $cart = cart_get_detalhado($pdo);
    if (empty($cart['itens'])) {
        flash_set('aviso', 'Seu carrinho está vazio.');
        redirect('carrinho.php');
    }

    $ids = array_map('intval', array_column($cart['itens'], 'id'));
    sort($ids);
    $ph = implode(',', array_fill(0, count($ids), '?'));

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT id, nome, preco, estoque, ativo
             FROM produtos
             WHERE id IN ($ph)
             FOR UPDATE"
        );
        $stmt->execute($ids);

        $fresh = [];
        foreach ($stmt->fetchAll() as $row) {
            $fresh[(int)$row['id']] = $row;
        }

        $total  = 0.0;
        $linhas = [];
        foreach ($cart['itens'] as $item) {
            $pid = (int)$item['id'];
            $qtd = (int)$item['quantidade'];

            if (!isset($fresh[$pid])) {
                throw new RuntimeException('Um dos produtos não está mais disponível.');
            }
            $p = $fresh[$pid];
            if (!(int)$p['ativo']) {
                throw new RuntimeException("O produto \"{$p['nome']}\" foi desativado.");
            }
            if ((int)$p['estoque'] < $qtd) {
                throw new RuntimeException(
                    "Estoque insuficiente para \"{$p['nome']}\". Disponível: " . (int)$p['estoque'] . "."
                );
            }

            $preco   = (float)$p['preco'];
            $total  += $preco * $qtd;
            $linhas[] = ['id' => $pid, 'qtd' => $qtd, 'preco' => $preco];
        }
        if (empty($linhas)) {
            throw new RuntimeException('Nenhum item para processar.');
        }
        $total = round($total, 2);

        $ins = $pdo->prepare(
            'INSERT INTO pedidos (usuario_id, forma_pagamento, total)
             VALUES (:u, :f, :t)'
        );
        $ins->execute([
            ':u' => (int)$usuario['id'],
            ':f' => $forma,
            ':t' => number_format($total, 2, '.', ''),
        ]);
        $pedido_id = (int)$pdo->lastInsertId();

        $ins_item = $pdo->prepare(
            'INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario)
             VALUES (:ped, :prod, :qtd, :preco)'
        );
        $upd_estoque = $pdo->prepare(
            'UPDATE produtos SET estoque = estoque - :delta
             WHERE id = :id AND estoque >= :minimo'
        );

        foreach ($linhas as $l) {
            $ins_item->execute([
                ':ped'   => $pedido_id,
                ':prod'  => $l['id'],
                ':qtd'   => $l['qtd'],
                ':preco' => number_format($l['preco'], 2, '.', ''),
            ]);

            $upd_estoque->execute([
                ':delta'  => $l['qtd'],
                ':id'     => $l['id'],
                ':minimo' => $l['qtd'],
            ]);

            if ($upd_estoque->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível reservar o estoque. Atualize o carrinho.');
            }
        }

        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $msg = $ex instanceof RuntimeException
            ? $ex->getMessage()
            : (APP_ENV === 'development'
                ? 'Erro ao processar o pedido: ' . $ex->getMessage()
                : 'Não foi possível concluir o pedido. Tente novamente.');
        flash_set('erro', $msg);
        redirect('checkout.php');
    }

    cart_clear();
    flash_set('sucesso', "Pedido #{$pedido_id} confirmado!");
    redirect('confirmacao.php?pedido=' . $pedido_id);
}

$cart = cart_get_detalhado($pdo);
foreach ($cart['avisos'] as $a) {
    flash_set('aviso', $a);
}
if (empty($cart['itens'])) {
    flash_set('aviso', 'Seu carrinho está vazio.');
    redirect('carrinho.php');
}

if (empty($_SESSION['checkout_token']) || !is_string($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(16));
}
$checkout_token = $_SESSION['checkout_token'];

$titulo = 'Finalizar compra';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <h1 class="mb-4">Finalizar compra</h1>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="auth-card mb-4">
        <h2 class="h5 mb-1">Dados do cliente</h2>
        <p class="small text-muted mb-3">O pedido será registrado em nome desta conta.</p>
        <dl class="row mb-0">
          <dt class="col-sm-3">Nome</dt>
          <dd class="col-sm-9"><?= e($usuario['nome']) ?></dd>
          <dt class="col-sm-3 mb-0">E-mail</dt>
          <dd class="col-sm-9 mb-0"><?= e($usuario['email']) ?></dd>
        </dl>
      </div>

      <form method="post" action="<?= e(url('checkout.php')) ?>" data-single-submit>
        <?= csrf_field() ?>
        <input type="hidden" name="checkout_token" value="<?= e($checkout_token) ?>">

        <fieldset class="auth-card mb-4">
          <legend class="h5 mb-3">Forma de pagamento</legend>

          <div class="forma-pagamento-grid">
            <label class="forma-pagamento">
              <input type="radio" name="forma_pagamento" value="pix" required>
              <span class="forma-titulo">PIX</span>
              <span class="forma-desc">Aprovação imediata (simulado)</span>
            </label>

            <label class="forma-pagamento">
              <input type="radio" name="forma_pagamento" value="boleto" required>
              <span class="forma-titulo">Boleto</span>
              <span class="forma-desc">Vencimento em 3 dias (simulado)</span>
            </label>

            <label class="forma-pagamento">
              <input type="radio" name="forma_pagamento" value="cartao" required>
              <span class="forma-titulo">Cartão</span>
              <span class="forma-desc">Crédito à vista (simulado)</span>
            </label>
          </div>

          <p class="small text-muted mt-3 mb-0">
            ⚠️ Pagamentos são apenas <strong>simulados</strong>. Nenhum valor é cobrado.
          </p>
        </fieldset>

        <div class="d-flex flex-wrap gap-2">
          <a href="<?= e(url('carrinho.php')) ?>" class="btn btn-outline-dark">
            ← Voltar ao carrinho
          </a>
          <button type="submit" class="btn btn-gold btn-lg flex-grow-1">
            Confirmar pedido
          </button>
        </div>
      </form>
    </div>

    <div class="col-lg-5">
      <div class="checkout-resumo">
        <h2 class="h5 mb-3">Resumo do pedido</h2>

        <ul class="list-unstyled mb-3">
          <?php foreach ($cart['itens'] as $item): ?>
            <li class="d-flex justify-content-between align-items-start gap-2 py-2 border-bottom">
              <span>
                <?= (int)$item['quantidade'] ?>× <?= e($item['nome']) ?>
                <span class="d-block small text-muted">
                  <?= e(preco_brl($item['preco_unitario'])) ?> cada
                </span>
              </span>
              <span class="fw-semibold text-nowrap"><?= e(preco_brl($item['subtotal'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="d-flex justify-content-between mb-2">
          <span>Itens (<?= (int)$cart['count'] ?>)</span>
          <span><?= e(preco_brl($cart['subtotal'])) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-3">
          <span>Frete</span>
          <span class="text-muted">A combinar</span>
        </div>

        <hr>

        <div class="d-flex justify-content-between">
          <strong>Total</strong>
          <strong class="produto-preco-detalhe"><?= e(preco_brl($cart['total'])) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>