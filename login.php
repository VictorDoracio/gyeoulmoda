<?php
declare(strict_types=1);

/** login.php — Login com password_verify, CSRF e preservação de intenção (?next=). */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

$next_raw = $_POST['next'] ?? $_GET['next'] ?? '';
$next = is_string($next_raw) ? next_seguro($next_raw, '') : '';

if (is_logado()) {
    redirect($next !== '' ? $next : 'index.php');
}

$erros = [];

if (is_post()) {
    csrf_verify();

    $email = trim((string)($_POST['email'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    } elseif ($senha === '') {
        $erros[] = 'Informe sua senha.';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT id, nome, email, senha_hash, perfil
             FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($senha, $u['senha_hash'])) {
            $erros[] = 'E-mail ou senha incorretos.';
        } else {
            login_usuario($u);
            old_clear();
            flash_set('sucesso', 'Bem-vindo(a) de volta, ' . $u['nome'] . '!');
            redirect($next !== '' ? $next : 'index.php');
        }
    }

    if ($erros) {
        old_set(['email' => $email]);
        foreach ($erros as $er) {
            flash_set('erro', $er);
        }
        $qs = $next !== '' ? '?next=' . urlencode($next) : '';
        redirect('login.php' . $qs);
    }
}

$titulo = 'Entrar';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <div class="auth-wrap">
    <div class="auth-card">
      <h1>Entrar</h1>
      <p class="text-muted small mb-4">
        Ainda não tem conta?
        <a href="<?= e(url('cadastro.php')) ?>">Cadastre-se</a>.
      </p>

      <form method="post" action="<?= e(url('login.php')) ?>">
        <?= csrf_field() ?>
        <?php if ($next !== ''): ?>
          <input type="hidden" name="next" value="<?= e($next) ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label for="email" class="form-label">E-mail</label>
          <input type="email" id="email" name="email" class="form-control"
                 maxlength="190" required autocomplete="email"
                 value="<?= e(old('email')) ?>">
        </div>

        <div class="mb-4">
          <label for="senha" class="form-label">Senha</label>
          <input type="password" id="senha" name="senha" class="form-control"
                 required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-gold w-100">Entrar</button>
      </form>
    </div>
  </div>
</div>

<?php old_clear(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>