<?php
declare(strict_types=1);

/**
 * admin/produtos.php — CRUD de produtos.
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

/** Processa upload. Retorna ['ok'=>bool,'filename'=>?string,'erro'=>?string]. */
function admin_upload_imagem(string $campo = 'imagem'): array
{
    if (empty($_FILES[$campo]) || ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => null, 'erro' => null];
    }
    $f = $_FILES[$campo];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'erro' => 'Falha no upload (código ' . (int)$f['error'] . ').'];
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'filename' => null, 'erro' => 'A imagem deve ter no máximo 2 MB.'];
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'filename' => null, 'erro' => 'Formato inválido. Use JPG, PNG ou WEBP.'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? (string)finfo_file($finfo, $f['tmp_name']) : '';
    if ($finfo) finfo_close($finfo);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return ['ok' => false, 'filename' => null, 'erro' => 'O arquivo não é uma imagem válida.'];
    }
    $dir = __DIR__ . '/../assets/img/produtos';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return ['ok' => false, 'filename' => null, 'erro' => 'Pasta de imagens indisponível.'];
    }
    $nome = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nome)) {
        return ['ok' => false, 'filename' => null, 'erro' => 'Falha ao salvar a imagem.'];
    }
    return ['ok' => true, 'filename' => $nome, 'erro' => null];
}

/** Converte preço em formato BR (aceita "129,90" e "129.90"). */
function parse_preco_brl(string $v): ?float
{
    $v = trim(str_replace(['R$', ' '], '', $v));
    if ($v === '') return null;
    if (str_contains($v, ',')) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    if (!is_numeric($v)) return null;
    $f = (float)$v;
    if ($f <= 0 || $f > 999999.99) return null;
    return round($f, 2);
}

// ============ POST ============
if (is_post()) {
    csrf_verify();
    $acao = (string)($_POST['acao'] ?? '');

    if ($acao === 'salvar') {
        $id            = (int)(filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0);
        $nome          = trim((string)($_POST['nome'] ?? ''));
        $descricao     = trim((string)($_POST['descricao'] ?? ''));
        $preco         = parse_preco_brl((string)($_POST['preco'] ?? ''));
        $estoque_raw   = trim((string)($_POST['estoque'] ?? ''));
        $categoria_id  = (int)(filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT) ?: 0);
        $ativo         = isset($_POST['ativo']) ? 1 : 0;
        $imagem_manual = trim((string)($_POST['imagem_manual'] ?? ''));

        $erros = [];
        $len = mb_strlen($nome);
        if ($len < 2 || $len > 160)  $erros[] = 'O nome deve ter entre 2 e 160 caracteres.';
        if ($descricao === '')       $erros[] = 'Descrição é obrigatória.';
        if ($preco === null)         $erros[] = 'Preço inválido (use "129,90" ou "129.90").';
        if (!ctype_digit($estoque_raw)) $erros[] = 'Estoque deve ser um inteiro ≥ 0.';
        $estoque = (int)$estoque_raw;

        $chk = $pdo->prepare('SELECT id FROM categorias WHERE id = :id');
        $chk->execute([':id' => $categoria_id]);
        if (!$chk->fetch()) $erros[] = 'Selecione uma categoria válida.';

        $up = admin_upload_imagem('imagem');
        if (!$up['ok']) $erros[] = $up['erro'];

        $img_final = null;
        if ($up['filename'] !== null) {
            $img_final = $up['filename'];
        } elseif ($imagem_manual !== '') {
            if (!preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp|svg)$/i', $imagem_manual)) {
                $erros[] = 'Nome de imagem inválido.';
            } else {
                $img_final = basename($imagem_manual);
            }
        }

        if ($erros) {
            foreach ($erros as $er) flash_set('erro', $er);
            old_set([
                'nome' => $nome, 'descricao' => $descricao,
                'preco' => (string)($_POST['preco'] ?? ''),
                'estoque' => $estoque_raw, 'categoria_id' => (string)$categoria_id,
                'ativo' => (string)$ativo, 'imagem_manual' => $imagem_manual,
            ]);
            $url = $id > 0
                ? 'admin/produtos.php?acao=editar&id=' . $id
                : 'admin/produtos.php?acao=novo';
            redirect($url);
        }

        if ($id > 0) {
            if ($img_final === null) {
                $cur = $pdo->prepare('SELECT imagem FROM produtos WHERE id = :id');
                $cur->execute([':id' => $id]);
                $img_final = (string)($cur->fetchColumn() ?: 'sem-imagem.svg');
            }
            $stmt = $pdo->prepare(
                'UPDATE produtos
                 SET categoria_id=:cat, nome=:nome, descricao=:desc,
                     preco=:preco, estoque=:est, imagem=:img, ativo=:ativo
                 WHERE id=:id'
            );
            $stmt->execute([
                ':cat'   => $categoria_id,
                ':nome'  => $nome,
                ':desc'  => $descricao,
                ':preco' => number_format((float)$preco, 2, '.', ''),
                ':est'   => $estoque,
                ':img'   => $img_final,
                ':ativo' => $ativo,
                ':id'    => $id,
            ]);
            flash_set('sucesso', 'Produto atualizado.');
        } else {
            if ($img_final === null) $img_final = 'sem-imagem.svg';
            $stmt = $pdo->prepare(
                'INSERT INTO produtos
                    (categoria_id, nome, descricao, preco, estoque, imagem, ativo)
                 VALUES (:cat, :nome, :desc, :preco, :est, :img, :ativo)'
            );
            $stmt->execute([
                ':cat'   => $categoria_id,
                ':nome'  => $nome,
                ':desc'  => $descricao,
                ':preco' => number_format((float)$preco, 2, '.', ''),
                ':est'   => $estoque,
                ':img'   => $img_final,
                ':ativo' => $ativo,
            ]);
            flash_set('sucesso', 'Produto criado.');
        }
        old_clear();
        redirect('admin/produtos.php');
    }

    if (in_array($acao, ['ativar', 'desativar', 'excluir'], true)) {
        $id = (int)(filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0);
        if ($id < 1) {
            flash_set('erro', 'Produto inválido.');
            redirect('admin/produtos.php');
        }
        if ($acao === 'excluir') {
            try {
                $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
                $stmt->execute([':id' => $id]);
                flash_set($stmt->rowCount() === 1 ? 'sucesso' : 'erro',
                    $stmt->rowCount() === 1 ? 'Produto excluído.' : 'Produto não encontrado.');
            } catch (PDOException $ex) {
                if (($ex->getCode() ?? '') === '23000') {
                    flash_set('erro', 'Este produto possui pedidos vinculados. Use "Desativar" em vez de "Excluir".');
                } else {
                    flash_set('erro', 'Não foi possível excluir o produto.');
                }
            }
        } else {
            $novo = $acao === 'ativar' ? 1 : 0;
            $stmt = $pdo->prepare('UPDATE produtos SET ativo = :a WHERE id = :id');
            $stmt->execute([':a' => $novo, ':id' => $id]);
            flash_set('sucesso', $novo ? 'Produto ativado.' : 'Produto desativado.');
        }
        redirect('admin/produtos.php');
    }

    flash_set('erro', 'Ação inválida.');
    redirect('admin/produtos.php');
}

// ============ GET ============
$acao = (string)($_GET['acao'] ?? 'listar');

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

$produto = null;
if ($acao === 'editar') {
    $id = (int)(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $produto = $stmt->fetch() ?: null;
    }
    if (!$produto) {
        flash_set('erro', 'Produto não encontrado.');
        redirect('admin/produtos.php');
    }
}

$busca = trim((string)($_GET['busca'] ?? ''));
$busca = mb_substr($busca, 0, 100);

$titulo = match ($acao) {
    'novo'   => 'Novo produto',
    'editar' => 'Editar produto',
    default  => 'Produtos',
};
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_nav.php';
?>

<div class="container py-4">

<?php if ($acao === 'novo' || $acao === 'editar'):
    $editing = $acao === 'editar' && $produto !== null;
    $pid     = $editing ? (int)$produto['id'] : 0;
    $v_nome  = old('nome', $editing ? $produto['nome'] : '');
    $v_desc  = old('descricao', $editing ? $produto['descricao'] : '');
    $v_preco = old('preco', $editing ? number_format((float)$produto['preco'], 2, ',', '') : '');
    $v_est   = old('estoque', $editing ? (string)$produto['estoque'] : '0');
    $v_cat   = (int)old('categoria_id', $editing ? (string)$produto['categoria_id'] : '0');
    $v_ativ  = old('ativo', $editing ? (string)$produto['ativo'] : '1') === '1';
    $v_img_m = old('imagem_manual', '');
?>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><?= $editing ? 'Editar produto' : 'Novo produto' ?></h1>
    <a href="<?= e(url('admin/produtos.php')) ?>" class="btn btn-outline-dark">← Voltar</a>
  </div>

  <form method="post" action="<?= e(url('admin/produtos.php')) ?>"
        enctype="multipart/form-data" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="id" value="<?= $pid ?>">

    <div class="row g-3">
      <div class="col-md-8">
        <label for="nome" class="form-label">Nome do produto *</label>
        <input type="text" id="nome" name="nome" class="form-control"
               maxlength="160" required value="<?= e($v_nome) ?>">
      </div>
      <div class="col-md-4">
        <label for="categoria_id" class="form-label">Categoria *</label>
        <select id="categoria_id" name="categoria_id" class="form-select" required>
          <option value="">Selecione…</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $v_cat === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label for="preco" class="form-label">Preço (R$) *</label>
        <input type="text" id="preco" name="preco" class="form-control"
               inputmode="decimal" required value="<?= e($v_preco) ?>"
               placeholder="Ex.: 129,90">
        <div class="form-text">Aceita vírgula ou ponto.</div>
      </div>
      <div class="col-md-4">
        <label for="estoque" class="form-label">Estoque *</label>
        <input type="number" id="estoque" name="estoque" class="form-control"
               min="0" step="1" required value="<?= e($v_est) ?>">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                 value="1" <?= $v_ativ ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Produto ativo (visível na vitrine)</label>
        </div>
      </div>

      <div class="col-12">
        <label for="descricao" class="form-label">Descrição *</label>
        <textarea id="descricao" name="descricao" class="form-control"
                  rows="5" required><?= e($v_desc) ?></textarea>
      </div>

      <div class="col-md-6">
        <label for="imagem" class="form-label">Upload de imagem</label>
        <input type="file" id="imagem" name="imagem" class="form-control"
               accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">JPG/PNG/WEBP, até 2 MB. Preenche automaticamente no envio.</div>
      </div>
      <div class="col-md-6">
        <label for="imagem_manual" class="form-label">…ou nome do arquivo existente</label>
        <input type="text" id="imagem_manual" name="imagem_manual" class="form-control"
               value="<?= e($v_img_m) ?>" placeholder="ex.: casaco-01.jpg">
        <div class="form-text">Ignorado se você enviar um upload.</div>
      </div>

      <?php if ($editing): ?>
        <div class="col-12">
          <p class="form-label mb-1">Imagem atual</p>
          <img src="<?= e(url('assets/img/produtos/' . $produto['imagem'])) ?>"
               alt="Imagem atual"
               class="admin-thumb"
               onerror="this.onerror=null;this.src='<?= e(url('assets/img/sem-imagem.svg')) ?>';">
          <span class="ms-2 small text-muted"><?= e($produto['imagem']) ?></span>
        </div>
      <?php endif; ?>
    </div>

    <hr>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-gold">
        <?= $editing ? 'Salvar alterações' : 'Criar produto' ?>
      </button>
      <a href="<?= e(url('admin/produtos.php')) ?>" class="btn btn-outline-dark">Cancelar</a>
    </div>
  </form>

<?php else:
    if ($busca !== '') {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busca) . '%';
        $stmt = $pdo->prepare(
            'SELECT p.*, c.nome AS categoria
             FROM produtos p INNER JOIN categorias c ON c.id = p.categoria_id
             WHERE p.nome LIKE :b OR p.descricao LIKE :b
             ORDER BY p.ativo DESC, p.nome ASC'
        );
        $stmt->execute([':b' => $like]);
        $produtos = $stmt->fetchAll();
    } else {
        $produtos = $pdo->query(
            'SELECT p.*, c.nome AS categoria
             FROM produtos p INNER JOIN categorias c ON c.id = p.categoria_id
             ORDER BY p.ativo DESC, p.nome ASC'
        )->fetchAll();
    }
?>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
      <h1 class="mb-0">Produtos</h1>
      <p class="text-muted mb-0 small"><?= count($produtos) ?> produto<?= count($produtos) === 1 ? '' : 's' ?> no total.</p>
    </div>
    <div class="d-flex gap-2">
      <form method="get" action="<?= e(url('admin/produtos.php')) ?>" class="d-flex" role="search">
        <label for="busca" class="visually-hidden">Buscar</label>
        <input id="busca" type="search" name="busca" class="form-control form-control-sm me-2"
               value="<?= e($busca) ?>" placeholder="Buscar…">
        <button class="btn btn-sm btn-outline-dark" type="submit">Buscar</button>
      </form>
      <a href="<?= e(url('admin/produtos.php?acao=novo')) ?>" class="btn btn-gold">
        + Novo produto
      </a>
    </div>
  </div>

  <?php if (empty($produtos)): ?>
    <div class="empty-state">
      <p class="mb-1"><strong>Nenhum produto <?= $busca !== '' ? 'encontrado' : 'cadastrado' ?>.</strong></p>
      <?php if ($busca !== ''): ?>
        <a class="btn btn-outline-dark mt-2" href="<?= e(url('admin/produtos.php')) ?>">Limpar busca</a>
      <?php else: ?>
        <a class="btn btn-gold mt-2" href="<?= e(url('admin/produtos.php?acao=novo')) ?>">Criar o primeiro</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="admin-card table-responsive p-0">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Imagem</th>
            <th scope="col">Nome</th>
            <th scope="col">Categoria</th>
            <th scope="col" class="text-end">Preço</th>
            <th scope="col" class="text-center">Estoque</th>
            <th scope="col" class="text-center">Status</th>
            <th scope="col" class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($produtos as $p):
            $esgotado = (int)$p['estoque'] === 0;
        ?>
          <tr>
            <td><?= (int)$p['id'] ?></td>
            <td>
              <img src="<?= e(url('assets/img/produtos/' . $p['imagem'])) ?>"
                   alt=""
                   class="admin-thumb"
                   loading="lazy"
                   onerror="this.onerror=null;this.src='<?= e(url('assets/img/sem-imagem.svg')) ?>';">
            </td>
            <td><?= e($p['nome']) ?></td>
            <td><?= e($p['categoria']) ?></td>
            <td class="text-end"><?= e(preco_brl($p['preco'])) ?></td>
            <td class="text-center">
              <?php if ($esgotado): ?>
                <span class="badge bg-danger">0</span>
              <?php else: ?>
                <?= (int)$p['estoque'] ?>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ((int)$p['ativo']): ?>
                <span class="badge bg-success">Ativo</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inativo</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-dark"
                 href="<?= e(url('admin/produtos.php?acao=editar&id=' . (int)$p['id'])) ?>">Editar</a>

              <form method="post" action="<?= e(url('admin/produtos.php')) ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <?php if ((int)$p['ativo']): ?>
                  <button type="submit" name="acao" value="desativar"
                          class="btn btn-sm btn-outline-warning"
                          data-confirm="Desativar este produto (sai da vitrine)?">Desativar</button>
                <?php else: ?>
                  <button type="submit" name="acao" value="ativar"
                          class="btn btn-sm btn-outline-success">Ativar</button>
                <?php endif; ?>
                <button type="submit" name="acao" value="excluir"
                        class="btn btn-sm btn-outline-danger"
                        data-confirm="Excluir permanentemente? Só funciona se não houver pedidos vinculados.">
                  Excluir
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php endif; ?>
</div>

<?php old_clear(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>