<?php
declare(strict_types=1);
/** includes/footer.php — fecha <main>, rodapé e scripts. */
?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-4">
        <h2 class="footer-brand"><span class="brand-gold">GY</span>eoul Moda</h2>
        <p class="mb-0">Moda de inverno com elegância e conforto.</p>
      </div>
      <div class="col-md-4">
        <h3 class="footer-title">Navegar</h3>
        <ul class="list-unstyled mb-0">
          <li><a href="<?= e(url('index.php')) ?>">Vitrine</a></li>
          <li><a href="<?= e(url('carrinho.php')) ?>">Carrinho</a></li>
          <li><a href="<?= e(url('meus-pedidos.php')) ?>">Meus pedidos</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h3 class="footer-title">Atendimento</h3>
        <p class="mb-1">contato@gyeoulmoda.local</p>
        <p class="mb-0">Projeto acadêmico — 4º bimestre</p>
      </div>
    </div>
    <hr>
    <p class="text-center small mb-0">
      &copy; <?= date('Y') ?> Gyeoul Moda. Todos os direitos reservados.
    </p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>