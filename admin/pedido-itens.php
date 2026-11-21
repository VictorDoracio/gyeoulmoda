<?php
declare(strict_types=1);

/** admin/pedido-itens.php — Detalhe de um pedido. */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

require_admin();
$pdo = db();

$id = (int)(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
if ($id < 1) {
    flash_set('erro', 'Pedido inválido.');
    redirect('admin/pedidos.php');
}

$stmt = $pdo->prepare(
    'SELECT p.id, p.data, p.forma_pagamento, p.total,
            u.nome AS cliente, u.email AS cliente_email
     FROM pedidos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     WHERE p.id = :id'
);
$stmt->execute([':id' => $id]);
$pedido = $stmt->fetch();
if (!$pedido) {
    flash_set('erro', 'Pedido não encontrado.');
    redirect('admin/pedidos.php');
}

$stmt = $pdo->prepare(
    'SELECT pi.quantidade, pi.preco_unitario,
            p.id AS produto_id, p.nome, p.imagem,
            c.nome AS categoria
     FROM pedido_itens pi
     INNER JOIN produtos p   ON p.id = pi.produto_id
     INNER JOIN categorias c ON c.id = p.categoria_id
     WHERE pi.pedido_id = :ped
     ORDER BY pi.id'
);
$stmt->execute([':ped' => $id]);
$itens = $stmt->fetchAll();

$formas_label = ['pix' => 'PIX', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];
$dt = new DateTime($pedido['data']);

$titulo = 'Pedido #' . (int)$pedido['id'];
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_nav.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Pedido #<?= (int)$pedido['id'] ?></h1>
    <a href="<?= e(url('admin/pedidos.php')) ?>" class="btn btn-outline-dark">← Pedidos</a>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="admin-card mb-4">
        <h2 class="h5 mb-3">Itens</h2>
        <?php foreach ($itens as $it): ?>
          <div class="pedido-item">
            <img src="<?= e(url('assets/img/produtos/' . $it['imagem'])) ?>"
                 alt=""
                 onerror="this.onerror=null;this.src='<?= e(url('assets/img/sem-imagem.svg')) ?>';">
            <div>
              <p class="produto-cat mb-1"><?= e($it['categoria']) ?></p>
              <p class="mb-0 fw-semibold"><?= e($it['nome']) ?></p>
              <p class="small text-muted mb-0">
                <?= (int)$it['quantidade'] ?>× <?= e(preco_brl($it['preco_unitario'])) ?>
              </p>
            </div>
            <div class="text-end fw-semibold">
              <?= e(preco_brl((float)$it['preco_unitario'] * (int)$it['quantidade'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
        <hr>
        <div class="d-flex justify-content-between">
          <strong>Total</strong>
          <strong class="produto-preco-detalhe"><?= e(preco_brl($pedido['total'])) ?></strong>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-card">
        <h2 class="h5 mb-3">Informações</h2>
        <dl class="mb-0">
          <dt class="small text-muted">Cliente</dt>
          <dd><?= e($pedido['cliente']) ?><br>
              <span class="small text-muted"><?= e($pedido['cliente_email']) ?></span></dd>

          <dt class="small text-muted">Data</dt>
          <dd><?= e($dt->format('d/m/Y H:i')) ?></dd>

          <dt class="small text-muted mb-1">Forma de pagamento</dt>
          <dd class="mb-0">
            <?= e($formas_label[$pedido['forma_pagamento']] ?? $pedido['forma_pagamento']) ?>
            <span class="badge bg-light text-dark border ms-1">simulado</span>
          </dd>
        </dl>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>