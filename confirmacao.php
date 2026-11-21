<?php
declare(strict_types=1);

/**
 * confirmacao.php — Resumo pós-pedido.
 * Acessível ao dono do pedido e ao admin.
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

require_login();

$pdo = db();
$usuario = usuario_atual();

$id = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    $titulo = 'Pedido não encontrado';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-5 text-center">
      <h1 class="h3 mb-3">Pedido não encontrado</h1>
      <p class="text-muted">O pedido solicitado não existe.</p>
      <a class="btn btn-gold" href="<?= e(url('index.php')) ?>">Voltar à vitrine</a>
    </div>
    <?php require __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, usuario_id, data, forma_pagamento, total
     FROM pedidos WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$pedido = $stmt->fetch();

if (!$pedido
    || ((int)$pedido['usuario_id'] !== (int)$usuario['id'] && !is_admin())) {
    http_response_code(404);
    $titulo = 'Pedido não encontrado';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-5 text-center">
      <h1 class="h3 mb-3">Pedido não encontrado</h1>
      <p class="text-muted">O pedido não existe ou não pertence à sua conta.</p>
      <a class="btn btn-gold" href="<?= e(url('index.php')) ?>">Voltar à vitrine</a>
    </div>
    <?php require __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT pi.quantidade, pi.preco_unitario,
            p.nome, p.imagem,
            c.nome AS categoria
     FROM pedido_itens pi
     INNER JOIN produtos p    ON p.id = pi.produto_id
     INNER JOIN categorias c  ON c.id = p.categoria_id
     WHERE pi.pedido_id = :ped
     ORDER BY pi.id'
);
$stmt->execute([':ped' => $id]);
$itens = $stmt->fetchAll();

$formas_label = [
    'pix'    => 'PIX',
    'boleto' => 'Boleto',
    'cartao' => 'Cartão de Crédito',
];

$dt = new DateTime($pedido['data']);

$titulo = 'Pedido #' . (int)$pedido['id'];
require __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <div class="confirmacao-header">
    <div class="confirmacao-icone" aria-hidden="true">✓</div>
    <h1>Pedido confirmado!</h1>
    <p class="text-muted mb-0">
      Pedido <strong>#<?= (int)$pedido['id'] ?></strong> ·
      <?= e($dt->format('d/m/Y H:i')) ?>
    </p>
  </div>

  <div class="row g-4 justify-content-center">
    <div class="col-lg-8">
      <div class="auth-card mb-4">
        <h2 class="h5 mb-3">Itens do pedido</h2>

        <?php foreach ($itens as $it): ?>
          <div class="pedido-item">
            <img src="<?= e(url('assets/img/produtos/' . $it['imagem'])) ?>"
                 alt="<?= e($it['nome']) ?>"
                 loading="lazy"
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

        <div class="d-flex justify-content-between mt-3">
          <span>Frete</span>
          <span class="text-muted">A combinar</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between">
          <strong>Total</strong>
          <strong class="produto-preco-detalhe"><?= e(preco_brl($pedido['total'])) ?></strong>
        </div>
      </div>

      <div class="auth-card mb-4">
        <h2 class="h5 mb-3">Pagamento</h2>
        <p class="mb-1">
          Forma escolhida:
          <strong><?= e($formas_label[$pedido['forma_pagamento']] ?? $pedido['forma_pagamento']) ?></strong>
        </p>
        <p class="small text-muted mb-0">
          ⚠️ Pagamento <strong>simulado</strong> — nenhum valor foi cobrado.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <a href="<?= e(url('index.php')) ?>" class="btn btn-gold">Continuar comprando</a>
        <a href="<?= e(url('meus-pedidos.php')) ?>" class="btn btn-outline-dark">Meus pedidos</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>