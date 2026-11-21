<?php
declare(strict_types=1);

/** admin/index.php — Dashboard. */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

$kpis = [
    'clientes'  => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente'")->fetchColumn(),
    'produtos'  => (int)$pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn(),
    'esgotados' => (int)$pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND estoque = 0")->fetchColumn(),
    'pedidos'   => (int)$pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn(),
    'receita'   => (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM pedidos")->fetchColumn(),
];

$ultimos = $pdo->query(
    'SELECT p.id, p.data, p.forma_pagamento, p.total, u.nome AS cliente
     FROM pedidos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     ORDER BY p.data DESC, p.id DESC
     LIMIT 5'
)->fetchAll();

$baixo = $pdo->query(
    'SELECT id, nome, estoque
     FROM produtos
     WHERE ativo = 1 AND estoque BETWEEN 1 AND 5
     ORDER BY estoque ASC, nome ASC
     LIMIT 5'
)->fetchAll();

$formas_label = ['pix' => 'PIX', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];

$titulo = 'Dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_nav.php';
?>

<div class="container py-4">
  <h1 class="mb-1">Dashboard</h1>
  <p class="text-muted mb-4">Bem-vindo(a), <?= e(usuario_atual()['nome']) ?>.</p>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg">
      <div class="admin-stat">
        <span class="admin-stat-label">Clientes</span>
        <span class="admin-stat-value"><?= (int)$kpis['clientes'] ?></span>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
      <div class="admin-stat">
        <span class="admin-stat-label">Produtos ativos</span>
        <span class="admin-stat-value"><?= (int)$kpis['produtos'] ?></span>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
      <div class="admin-stat">
        <span class="admin-stat-label">Esgotados</span>
        <span class="admin-stat-value text-danger"><?= (int)$kpis['esgotados'] ?></span>
      </div>
    </div>
    <div class="col-6 col-md-6 col-lg">
      <div class="admin-stat">
        <span class="admin-stat-label">Pedidos</span>
        <span class="admin-stat-value"><?= (int)$kpis['pedidos'] ?></span>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg">
      <div class="admin-stat">
        <span class="admin-stat-label">Receita total</span>
        <span class="admin-stat-value"><?= e(preco_brl($kpis['receita'])) ?></span>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="admin-card h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 mb-0">Últimos pedidos</h2>
          <a href="<?= e(url('admin/pedidos.php')) ?>" class="small">Ver todos →</a>
        </div>

        <?php if (empty($ultimos)): ?>
          <p class="text-muted mb-0">Nenhum pedido ainda.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Cliente</th>
                  <th scope="col">Forma</th>
                  <th scope="col" class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ultimos as $o): ?>
                  <tr>
                    <td><a href="<?= e(url('admin/pedido-itens.php?id=' . (int)$o['id'])) ?>">#<?= (int)$o['id'] ?></a></td>
                    <td><?= e($o['cliente']) ?></td>
                    <td><?= e($formas_label[$o['forma_pagamento']] ?? $o['forma_pagamento']) ?></td>
                    <td class="text-end"><?= e(preco_brl($o['total'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="admin-card h-100">
        <h2 class="h5 mb-3">Estoque baixo (1 a 5)</h2>
        <?php if (empty($baixo)): ?>
          <p class="text-muted mb-0">Nenhum produto com estoque baixo.</p>
        <?php else: ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($baixo as $p): ?>
              <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <a href="<?= e(url('admin/produtos.php?acao=editar&id=' . (int)$p['id'])) ?>">
                  <?= e($p['nome']) ?>
                </a>
                <span class="badge bg-warning text-dark"><?= (int)$p['estoque'] ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>