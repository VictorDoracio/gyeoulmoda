<?php
declare(strict_types=1);

/**
 * meus-pedidos.php — Histórico de pedidos do usuário logado (bônus).
 * Detalhe de cada pedido é exibido em confirmacao.php?pedido=X
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

require_login();

$pdo     = db();
$usuario = usuario_atual();

$stmt = $pdo->prepare(
    'SELECT p.id, p.data, p.forma_pagamento, p.total,
            (SELECT COUNT(*) FROM pedido_itens pi WHERE pi.pedido_id = p.id) AS qtd_itens,
            (SELECT COALESCE(SUM(pi.quantidade), 0)
               FROM pedido_itens pi WHERE pi.pedido_id = p.id) AS qtd_unidades
     FROM pedidos p
     WHERE p.usuario_id = :uid
     ORDER BY p.data DESC, p.id DESC'
);
$stmt->execute([':uid' => (int)$usuario['id']]);
$pedidos = $stmt->fetchAll();

$formas_label = ['pix' => 'PIX', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];

$titulo = 'Meus pedidos';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h1 class="mb-0">Meus pedidos</h1>
      <p class="text-muted mb-0 small">
        <?= count($pedidos) ?> pedido<?= count($pedidos) === 1 ? '' : 's' ?> no seu histórico.
      </p>
    </div>
    <a href="<?= e(url('index.php')) ?>" class="btn btn-outline-dark">Continuar comprando</a>
  </div>

  <?php if (empty($pedidos)): ?>
    <div class="empty-state">
      <p class="mb-1"><strong>Você ainda não fez pedidos.</strong></p>
      <p class="text-muted mb-3">Quando finalizar uma compra, ela aparecerá aqui.</p>
      <a class="btn btn-gold" href="<?= e(url('index.php')) ?>">Ver produtos</a>
    </div>
  <?php else: ?>
    <div class="admin-card p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Pedido</th>
              <th scope="col">Data</th>
              <th scope="col">Forma</th>
              <th scope="col" class="text-center">Itens</th>
              <th scope="col" class="text-end">Total</th>
              <th scope="col" class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($pedidos as $p):
              $dt = new DateTime($p['data']);
          ?>
            <tr>
              <td><strong>#<?= (int)$p['id'] ?></strong></td>
              <td><?= e($dt->format('d/m/Y H:i')) ?></td>
              <td><?= e($formas_label[$p['forma_pagamento']] ?? $p['forma_pagamento']) ?></td>
              <td class="text-center">
                <?= (int)$p['qtd_itens'] ?> tipo<?= (int)$p['qtd_itens'] === 1 ? '' : 's' ?>
                <span class="d-block small text-muted"><?= (int)$p['qtd_unidades'] ?> un.</span>
              </td>
              <td class="text-end fw-semibold"><?= e(preco_brl($p['total'])) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-dark"
                   href="<?= e(url('confirmacao.php?pedido=' . (int)$p['id'])) ?>">
                  Ver detalhes
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>