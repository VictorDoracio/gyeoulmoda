<?php
declare(strict_types=1);

/** produto.php — Detalhe do produto. */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

$pdo = db();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    $titulo = 'Produto não encontrado';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-5 text-center">
      <h1 class="h3 mb-3">Produto não encontrado</h1>
      <p class="text-muted">O produto que você procura não existe ou foi removido.</p>
      <a class="btn btn-gold" href="<?= e(url('index.php')) ?>">Voltar à vitrine</a>
    </div>
    <?php require __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.imagem,
            c.nome AS categoria, c.slug AS categoria_slug
     FROM produtos p
     INNER JOIN categorias c ON c.id = p.categoria_id
     WHERE p.id = :id AND p.ativo = 1'
);
$stmt->execute([':id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    http_response_code(404);
    $titulo = 'Produto não encontrado';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-5 text-center">
      <h1 class="h3 mb-3">Produto não encontrado</h1>
      <p class="text-muted">O produto não está disponível.</p>
      <a class="btn btn-gold" href="<?= e(url('index.php')) ?>">Voltar à vitrine</a>
    </div>
    <?php require __DIR__ . '/includes/footer.php';
    exit;
}

$titulo = $produto['nome'];
require __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Vitrine</a></li>
      <li class="breadcrumb-item">
        <a href="<?= e(url('index.php?categoria=' . urlencode($produto['categoria_slug']))) ?>">
          <?= e($produto['categoria']) ?>
        </a>
      </li>
      <li class="breadcrumb-item active" aria-current="page"><?= e($produto['nome']) ?></li>
    </ol>
  </nav>

  <div class="row g-4 align-items-start">
    <div class="col-md-6">
      <div class="produto-detalhe-img">
        <img src="<?= e(url('assets/img/produtos/' . $produto['imagem'])) ?>"
             alt="<?= e($produto['nome']) ?>"
             onerror="this.onerror=null;this.src='<?= e(url('assets/img/sem-imagem.svg')) ?>';">
      </div>
    </div>

    <div class="col-md-6">
      <p class="produto-cat"><?= e($produto['categoria']) ?></p>
      <h1 class="h3 mb-3"><?= e($produto['nome']) ?></h1>
      <p class="produto-preco-detalhe mb-3"><?= e(preco_brl($produto['preco'])) ?></p>

      <?php if ((int)$produto['estoque'] > 0): ?>
        <p class="text-success mb-3">
          Em estoque — <?= (int)$produto['estoque'] ?> disponíve<?= (int)$produto['estoque'] === 1 ? 'l' : 'is' ?>
        </p>
      <?php else: ?>
        <p class="text-danger mb-3"><strong>Esgotado</strong></p>
      <?php endif; ?>

      <p class="text-muted"><?= nl2br(e($produto['descricao'])) ?></p>

      <?php if ((int)$produto['estoque'] > 0): ?>
        <form method="post" action="<?= e(url('carrinho.php')) ?>"
              class="row g-2 align-items-end mb-3">
          <?= csrf_field() ?>
          <input type="hidden" name="acao" value="add">
          <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
          <input type="hidden" name="voltar" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">

          <div class="col-auto">
            <label for="qtd" class="form-label small mb-1">Quantidade</label>
            <input type="number"
                   id="qtd"
                   name="quantidade"
                   class="form-control"
                   value="1"
                   min="1"
                   max="<?= (int)$produto['estoque'] ?>"
                   required
                   inputmode="numeric"
                   style="width: 90px">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-gold btn-lg">Adicionar ao carrinho</button>
          </div>
        </form>
      <?php else: ?>
        <button type="button" class="btn btn-outline-secondary btn-lg mb-3" disabled>Esgotado</button>
      <?php endif; ?>

      <a href="<?= e(url('index.php?categoria=' . urlencode($produto['categoria_slug']))) ?>"
         class="btn btn-link px-0">
        ← Ver mais em <?= e($produto['categoria']) ?>
      </a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>