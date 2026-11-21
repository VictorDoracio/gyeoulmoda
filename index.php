<?php
declare(strict_types=1);

/**
 * index.php — Vitrine.
 * Filtros: ?categoria=slug  e  ?busca=termo
 * Todos os filtros via GET e queries com prepared statements.
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

$pdo = db();

$categoria_slug = isset($_GET['categoria']) && is_string($_GET['categoria'])
    ? trim($_GET['categoria']) : '';
$busca = isset($_GET['busca']) && is_string($_GET['busca'])
    ? trim($_GET['busca']) : '';
$busca = mb_substr($busca, 0, 100);

$categorias = $pdo->query('SELECT id, nome, slug FROM categorias ORDER BY nome')->fetchAll();

$categoria_atual = null;
if ($categoria_slug !== '') {
    foreach ($categorias as $c) {
        if ($c['slug'] === $categoria_slug) { $categoria_atual = $c; break; }
    }
    if ($categoria_atual === null) { $categoria_slug = ''; }
}

$sql = 'SELECT p.id, p.nome, p.preco, p.estoque, p.imagem,
               c.nome AS categoria, c.slug AS categoria_slug
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        WHERE p.ativo = 1';
$params = [];

if ($categoria_slug !== '') {
    $sql .= ' AND c.slug = :slug';
    $params[':slug'] = $categoria_slug;
}

if ($busca !== '') {
    $termo = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busca) . '%';
    $sql .= ' AND (p.nome LIKE :busca_nome OR p.descricao LIKE :busca_desc)';
    $params[':busca_nome'] = $termo;
    $params[':busca_desc'] = $termo;
}

$sql .= ' ORDER BY p.criado_em DESC, p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$titulo = 'Vitrine';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <p class="hero-eyebrow">Coleção Inverno</p>
    <h1 class="hero-title">Elegância que aquece.</h1>
    <p class="hero-sub">Peças selecionadas para os dias mais frios.</p>
  </div>
</section>

<div class="container py-4">
  <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Filtro por categoria">
    <?php $qTodas = $busca !== '' ? ['busca' => $busca] : []; ?>
    <a href="<?= e(url('index.php' . ($qTodas ? '?' . http_build_query($qTodas) : ''))) ?>"
       class="chip <?= $categoria_slug === '' ? 'chip-active' : '' ?>">Todas</a>

    <?php foreach ($categorias as $c):
        $qs = ['categoria' => $c['slug']];
        if ($busca !== '') $qs['busca'] = $busca;
    ?>
      <a href="<?= e(url('index.php?' . http_build_query($qs))) ?>"
         class="chip <?= $categoria_slug === $c['slug'] ? 'chip-active' : '' ?>">
        <?= e($c['nome']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="d-flex justify-content-between align-items-baseline mb-3 flex-wrap gap-2">
    <h2 class="h5 mb-0">
      <?php if ($busca !== ''): ?>
        Resultados para “<?= e($busca) ?>”
      <?php elseif ($categoria_atual): ?>
        <?= e($categoria_atual['nome']) ?>
      <?php else: ?>
        Todos os produtos
      <?php endif; ?>
    </h2>
    <span class="text-muted small">
      <?= count($produtos) ?> produto<?= count($produtos) === 1 ? '' : 's' ?>
    </span>
  </div>

  <?php if (empty($produtos)): ?>
    <div class="empty-state">
      <p class="mb-1"><strong>Nenhum produto encontrado.</strong></p>
      <p class="text-muted mb-3">Tente outro termo ou explore outra categoria.</p>
      <a class="btn btn-outline-dark" href="<?= e(url('index.php')) ?>">Ver tudo</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($produtos as $p): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <?php require __DIR__ . '/includes/produto_card.php'; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>