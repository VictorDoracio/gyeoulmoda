<?php
declare(strict_types=1);

/**
 * includes/header.php
 * Pré-requisitos (incluídos ANTES, nesta ordem):
 *   config/env.php, includes/helpers.php, includes/auth.php,
 *   includes/csrf.php, config/database.php
 */

if (!function_exists('e') || !function_exists('is_logado')) {
    http_response_code(500);
    exit('includes/helpers.php e includes/auth.php devem ser incluídos antes de header.php.');
}

$titulo = $titulo ?? 'Gyeoul Moda';

$busca_header = isset($_GET['busca']) && is_string($_GET['busca']) ? trim($_GET['busca']) : '';

$categorias_nav = [];
try {
    $categorias_nav = db()->query('SELECT nome, slug FROM categorias ORDER BY nome')->fetchAll();
} catch (Throwable $__ex) {
    // banco ainda não pronto: site abre sem categorias no menu
}

$__cart_count = 0;
if (!empty($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $__qtd) {
        $__cart_count += (int)$__qtd;
    }
}

$__flash_map = ['aviso' => 'warning', 'sucesso' => 'success', 'erro' => 'danger', 'info' => 'info'];
$__usuario   = usuario_atual();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?> — Gyeoul Moda</title>
  <meta name="description" content="Gyeoul Moda — moda de inverno com elegância e conforto.">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>

<header class="site-header">
  <nav class="navbar navbar-expand-lg" aria-label="Navegação principal">
    <div class="container">
      <a class="navbar-brand" href="<?= e(url('index.php')) ?>">
        <span class="brand-gold">GY</span>eoul&nbsp;Moda
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
              data-bs-target="#navbarPrincipal" aria-controls="navbarPrincipal"
              aria-expanded="false" aria-label="Alternar navegação">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarPrincipal">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="<?= e(url('index.php')) ?>">Início</a>
          </li>
          <?php if (!empty($categorias_nav)): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false">Categorias</a>
              <ul class="dropdown-menu">
                <?php foreach ($categorias_nav as $c): ?>
                  <li>
                    <a class="dropdown-item"
                       href="<?= e(url('index.php?categoria=' . urlencode($c['slug']))) ?>">
                      <?= e($c['nome']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php endif; ?>
        </ul>

        <form class="d-flex me-lg-3 mb-2 mb-lg-0" role="search" method="get"
              action="<?= e(url('index.php')) ?>">
          <label for="busca-nav" class="visually-hidden">Buscar produtos</label>
          <input id="busca-nav" class="form-control form-control-sm me-2" type="search"
                 name="busca" placeholder="Buscar…" value="<?= e($busca_header) ?>">
          <button class="btn btn-sm btn-outline-dark" type="submit">Buscar</button>
        </form>

        <ul class="navbar-nav align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link" href="<?= e(url('carrinho.php')) ?>">
              Carrinho
              <?php if ($__cart_count > 0): ?>
                <span class="badge cart-badge"><?= (int)$__cart_count ?></span>
              <?php endif; ?>
            </a>
          </li>

          <?php if ($__usuario): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false">
                <?= e($__usuario['nome']) ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= e(url('meus-pedidos.php')) ?>">Meus pedidos</a></li>
                <?php if (is_admin()): ?>
                  <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>">Painel admin</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(url('logout.php')) ?>">Sair</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= e(url('login.php')) ?>">Entrar</a>
            </li>
            <li class="nav-item ms-lg-2">
              <a class="nav-link btn btn-sm btn-gold" href="<?= e(url('cadastro.php')) ?>">Cadastrar</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>

<main>
<?php $__flashes = flash_get(); if (!empty($__flashes)): ?>
  <div class="container pt-3">
    <?php foreach ($__flashes as $f): ?>
      <div class="alert alert-<?= e($__flash_map[$f['tipo']] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= e($f['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>