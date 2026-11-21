<?php
declare(strict_types=1);

/** admin/pedidos.php — Listagem de pedidos. */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

require_admin();
$pdo = db();

$pedidos = $pdo->query(
    'SELECT p.id, p.data, p.forma_pagamento, p.total,
            u.nome AS cliente, u.email AS cliente_email,
            (SELECT COUNT(*) FROM pedido_itens pi WHERE pi.pedido_id = p.id) AS qtd_itens
     FROM pedidos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     ORDER BY p.data DESC, p.id DESC'
)->fetchAll();

$formas_label = ['pix' => 'PIX', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];

$titulo = 'Pedidos';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_nav.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-0">Pedidos</h1>
      <p class="text-muted mb-0 small"><?= count($pedidos) ?> pedido<?= count($pedidos) === 1 ? '' : 's' ?> no total.</p>
    </div>
  </div>

  <?php if (empty($pedidos)): ?>
    <div class="empty-state">
      <p class="mb-1"><strong>Nenhum pedido ainda.</strong></p>
      <p class="text-muted mb-0">Os pedidos aparecerão aqui quando os clientes finalizarem compras.</p>
    </div>
  <?php else: ?>
    <div class="admin-card table-responsive p-0">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Data</th>
            <th scope="col">Cliente</th>
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
            <td>#<?= (int)$p['id'] ?></td>
            <td><?= e($dt->format('d/m/Y H:i')) ?></td>
            <td>
              <?= e($p['cliente']) ?>
              <span class="d-block small text-muted"><?= e($p['cliente_email']) ?></span>
            </td>
            <td><?= e($formas_label[$p['forma_pagamento']] ?? $p['forma_pagamento']) ?></td>
            <td class="text-center"><?= (int)$p['qtd_itens'] ?></td>
            <td class="text-end fw-semibold"><?= e(preco_brl($p['total'])) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-dark"
                 href="<?= e(url('admin/pedido-itens.php?id=' . (int)$p['id'])) ?>">Ver detalhes</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>