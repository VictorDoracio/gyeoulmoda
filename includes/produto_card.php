<?php
declare(strict_types=1);
/**
 * includes/produto_card.php
 * Renderiza um card de produto.
 * Espera $p com: id, nome, preco, estoque, imagem, categoria.
 * Requer includes/helpers.php e includes/csrf.php carregados.
 */
?>
<article class="produto-card">
  <a href="<?= e(url('produto.php?id=' . (int)$p['id'])) ?>"
     class="produto-img-wrap"
     aria-label="Ver detalhes de <?= e($p['nome']) ?>">
    <img src="<?= e(url('assets/img/produtos/' . $p['imagem'])) ?>"
         alt="<?= e($p['nome']) ?>"
         class="produto-img"
         loading="lazy"
         onerror="this.onerror=null;this.src='<?= e(url('assets/img/sem-imagem.svg')) ?>';">
    <?php if ((int)$p['estoque'] <= 0): ?>
      <span class="badge-esgotado">Esgotado</span>
    <?php endif; ?>
  </a>

  <div class="produto-body">
    <p class="produto-cat"><?= e($p['categoria'] ?? '') ?></p>
    <h3 class="produto-nome">
      <a href="<?= e(url('produto.php?id=' . (int)$p['id'])) ?>"><?= e($p['nome']) ?></a>
    </h3>
    <p class="produto-preco"><?= e(preco_brl($p['preco'])) ?></p>

    <?php if ((int)$p['estoque'] > 0): ?>
      <form method="post" action="<?= e(url('carrinho.php')) ?>" class="mt-auto">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="add">
        <input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
        <input type="hidden" name="quantidade" value="1">
        <input type="hidden" name="voltar" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
        <button type="submit" class="btn btn-gold w-100">Adicionar</button>
      </form>
    <?php else: ?>
      <button type="button" class="btn btn-outline-secondary w-100" disabled>Esgotado</button>
    <?php endif; ?>
  </div>
</article>