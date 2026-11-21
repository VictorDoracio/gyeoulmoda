<?php
declare(strict_types=1);

/**
 * carrinho.php — Carrinho (visualizar / adicionar / atualizar / remover / esvaziar).
 * Acessível a visitantes; o login só é exigido no checkout.
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = db();

if (is_post()) {
    csrf_verify();

    $acao       = (string)($_POST['acao'] ?? '');
    $produto_id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
    $qtd        = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    if ($produto_id === false || $produto_id === null) $produto_id = 0;
    if ($qtd === false || $qtd === null)              $qtd = 0;

    switch ($acao) {
        case 'add':
            if ($qtd < 1) {
                flash_set('erro', 'Quantidade inválida.');
                break;
            }
            $r = cart_add((int)$produto_id, (int)$qtd, $pdo);
            flash_set($r['ok'] ? 'sucesso' : 'erro', $r['msg']);

            $voltar = $_POST['voltar'] ?? '';
            if (is_string($voltar) && $voltar !== '') {
                redirect(next_seguro($voltar, 'carrinho.php'));
            }
            break;

        case 'update':
            $r = cart_update((int)$produto_id, (int)$qtd, $pdo);
            flash_set($r['ok'] ? 'sucesso' : 'aviso', $r['msg']);
            break;

        case 'remove':
            $r = cart_remove((int)$produto_id);
            flash_set($r['ok'] ? 'info' : 'erro', $r['msg']);
            break;

        case 'clear':
            cart_clear();
            flash_set('info', 'Carrinho esvaziado.');
            break;

        default:
            flash_set('erro', 'Ação inválida.');
    }

    redirect('carrinho.php');
}

$cart = cart_get_detalhado($pdo);
foreach ($cart['avisos'] as $aviso) {
    flash_set('aviso', $aviso);
}

$titulo = 'Carrinho';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <h1 class="mb-4">Seu carrinho</h1>

  <?php if (empty($cart['itens'])): ?>
    <div class="empty-state">
      <p class="mb-1"><strong>Seu carrinho está vazio.</strong></p>
      <p class="text-muted mb-3">Explore nossa coleção de inverno.</p>
      <a class="btn btn-gold" href="<?= e(url('index.php')) ?>">Ver produtos</a>
    </div>

  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="cart-list">
          <?php foreach ($cart['itens'] as $item): ?>
            <div class="cart-item">
              <div class="cart-item-img">
                <a href="<?= e(url('produto.php?id=' . (int)$item['id'])) ?>">
                  <img src="<?= e(url('assets/img/produtos/' . $item['imagem'])) ?>"
                       alt="<?= e($item['nome']) ?>"
                       onerror="this.onerror=null;this.src='<?= e(url('assets/img/sem-imagem.svg')) ?>';">
                </a>
              </div>

              <div class="cart-item-info">
                <p class="produto-cat mb-1"><?= e($item['categoria']) ?></p>
                <h2 class="h6 mb-1">
                  <a href="<?= e(url('produto.php?id=' . (int)$item['id'])) ?>"><?= e($item['nome']) ?></a>
                </h2>
                <p class="small text-muted mb-0"><?= e(preco_brl($item['preco_unitario'])) ?> cada</p>
                <p class="small text-muted mb-0">Estoque: <?= (int)$item['estoque'] ?></p>
              </div>

              <div class="cart-item-qty">
                <form method="post" action="<?= e(url('carrinho.php')) ?>"
                      class="d-flex align-items-center gap-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="update">
                  <input type="hidden" name="produto_id" value="<?= (int)$item['id'] ?>">
                  <label class="visually-hidden" for="qtd-<?= (int)$item['id'] ?>">Quantidade</label>
                  <input type="number"
                         id="qtd-<?= (int)$item['id'] ?>"
                         name="quantidade"
                         class="form-control form-control-sm"
                         value="<?= (int)$item['quantidade'] ?>"
                         min="1"
                         max="<?= (int)$item['estoque'] ?>"
                         required
                         inputmode="numeric"
                         style="width:80px">
                  <button type="submit" class="btn btn-sm btn-outline-dark">Atualizar</button>
                </form>
              </div>

              <div class="cart-item-sub">
                <p class="fw-bold mb-1"><?= e(preco_brl($item['subtotal'])) ?></p>
                <form method="post" action="<?= e(url('carrinho.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="remove">
                  <input type="hidden" name="produto_id" value="<?= (int)$item['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                          data-confirm="Remover este item do carrinho?">Remover</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between flex-wrap gap-2 mt-3">
          <a href="<?= e(url('index.php')) ?>" class="btn btn-outline-dark">Continuar comprando</a>
          <form method="post" action="<?= e(url('carrinho.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="acao" value="clear">
            <button type="submit" class="btn btn-outline-danger"
                    data-confirm="Esvaziar todo o carrinho?">Esvaziar carrinho</button>
          </form>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="cart-resumo">
          <h2 class="h5 mb-3">Resumo</h2>

          <div class="d-flex justify-content-between mb-2">
            <span>Itens (<?= (int)$cart['count'] ?>)</span>
            <span><?= e(preco_brl($cart['subtotal'])) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span>Frete</span>
            <span class="text-muted">A combinar</span>
          </div>

          <hr>

          <div class="d-flex justify-content-between mb-3">
            <strong>Total</strong>
            <strong class="produto-preco-detalhe"><?= e(preco_brl($cart['total'])) ?></strong>
          </div>

          <a href="<?= e(url('checkout.php')) ?>" class="btn btn-gold w-100 mb-2">
            Finalizar compra
          </a>
          <?php if (!is_logado()): ?>
            <p class="small text-muted mb-0 text-center">
              Você fará login ou criará uma conta no checkout.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>