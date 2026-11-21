<?php
declare(strict_types=1);
/** admin/_nav.php — Sub-navegação do painel. */
if (!function_exists('e')) { http_response_code(500); exit('Contexto inválido.'); }

$__current = basename($_SERVER['PHP_SELF'] ?? '');
$__links = [
    'index.php'    => 'Dashboard',
    'produtos.php' => 'Produtos',
    'pedidos.php'  => 'Pedidos',
];
?>
<nav class="admin-nav" aria-label="Navegação administrativa">
  <div class="container">
    <ul class="nav">
      <?php foreach ($__links as $file => $label): ?>
        <li class="nav-item">
          <a class="nav-link <?= $__current === $file ? 'active' : '' ?>"
             href="<?= e(url('admin/' . $file)) ?>"><?= e($label) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>