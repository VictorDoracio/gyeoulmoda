<?php
declare(strict_types=1);

/** cadastro.php — Cadastro de cliente com hash, CSRF e validação server-side. */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

if (is_logado()) {
    redirect('index.php');
}

$erros = [];

if (is_post()) {
    csrf_verify();

    $nome   = trim((string)($_POST['nome'] ?? ''));
    $email  = trim((string)($_POST['email'] ?? ''));
    $senha  = (string)($_POST['senha'] ?? '');
    $senha2 = (string)($_POST['senha_confirma'] ?? '');

    $len_nome = mb_strlen($nome);
    if ($len_nome < 2 || $len_nome > 120) {
        $erros[] = 'Informe seu nome (entre 2 e 120 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif (strlen($senha) > 72) {
        $erros[] = 'A senha deve ter no máximo 72 caracteres.';
    }
    if ($senha !== $senha2) {
        $erros[] = 'As senhas não coincidem.';
    }

    if (empty($erros)) {
        $pdo = db();
        try {
            $chk = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $chk->execute([':email' => $email]);

            if ($chk->fetch()) {
                $erros[] = 'Este e-mail já está cadastrado.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);

                $ins = $pdo->prepare(
                    'INSERT INTO usuarios (nome, email, senha_hash, perfil)
                     VALUES (:nome, :email, :hash, :perfil)'
                );
                $ins->execute([
                    ':nome'   => $nome,
                    ':email'  => $email,
                    ':hash'   => $hash,
                    ':perfil' => 'cliente',
                ]);

                login_usuario([
                    'id'     => (int)$pdo->lastInsertId(),
                    'nome'   => $nome,
                    'email'  => $email,
                    'perfil' => 'cliente',
                ]);

                old_clear();
                flash_set('sucesso', 'Cadastro concluído. Bem-vindo(a), ' . $nome . '!');
                redirect('index.php');
            }
        } catch (PDOException $ex) {
            if (($ex->getCode() ?? '') === '23000') {
                $erros[] = 'Este e-mail já está cadastrado.';
            } else {
                $erros[] = (APP_ENV === 'development')
                    ? 'Erro ao cadastrar: ' . $ex->getMessage()
                    : 'Não foi possível concluir o cadastro. Tente novamente.';
            }
        }
    }

    if ($erros) {
        old_set(['nome' => $nome, 'email' => $email]);
        foreach ($erros as $er) {
            flash_set('erro', $er);
        }
        redirect('cadastro.php');
    }
}

$titulo = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <div class="auth-wrap">
    <div class="auth-card">
      <h1>Criar conta</h1>
      <p class="text-muted small mb-4">
        Já tem cadastro?
        <a href="<?= e(url('login.php')) ?>">Entrar</a>.
      </p>

      <form method="post" action="<?= e(url('cadastro.php')) ?>">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label for="nome" class="form-label">Nome completo</label>
          <input type="text" id="nome" name="nome" class="form-control"
                 maxlength="120" required autocomplete="name"
                 value="<?= e(old('nome')) ?>">
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">E-mail</label>
          <input type="email" id="email" name="email" class="form-control"
                 maxlength="190" required autocomplete="email"
                 value="<?= e(old('email')) ?>">
        </div>

        <div class="mb-3">
          <label for="senha" class="form-label">Senha</label>
          <input type="password" id="senha" name="senha" class="form-control"
                 minlength="8" maxlength="72" required
                 autocomplete="new-password">
          <div class="form-text">Mínimo de 8 caracteres.</div>
        </div>

        <div class="mb-4">
          <label for="senha_confirma" class="form-label">Confirmar senha</label>
          <input type="password" id="senha_confirma" name="senha_confirma"
                 class="form-control" minlength="8" maxlength="72" required
                 autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-gold w-100">Criar conta</button>
      </form>
    </div>
  </div>
</div>

<?php old_clear(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>