<?php
require_once '../config/auth.php';
exigir_perfil(['usuario']);

$erro = '';
$v_titulo = '';
$v_desc = '';
$v_prio = 'media';
$v_cat = 0;
$categorias = $pdo->query("SELECT * FROM categoria ORDER BY nome_categoria")->fetchAll(PDO::FETCH_ASSOC);

// extensões aceitas e o tipo real esperado para cada uma
$tipos_ok = [
    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'gif' => 'image/gif', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v_titulo = trim($_POST['titulo'] ?? '');
    $v_desc   = trim($_POST['descricao'] ?? '');
    $v_prio   = $_POST['prioridade'] ?? 'media';
    $v_cat    = (int)($_POST['categoria'] ?? 0);

    $arquivo = $_FILES['anexo'] ?? null;
    $tem_arquivo = $arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE;
    $ext = '';

    if ($v_titulo === '') {
        $erro = 'O campo Título é obrigatório.';
    } elseif ($v_desc === '') {
        $erro = 'O campo Descrição é obrigatório.';
    } elseif ($v_cat === 0) {
        $erro = 'O campo Categoria é obrigatório.';
    } elseif (!in_array($v_prio, ['baixa', 'media', 'alta'])) {
        $erro = 'Prioridade inválida.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = ?");
        $stmt->execute([$v_cat]);
        if ($stmt->fetchColumn() == 0) {
            $erro = 'Categoria inválida.';
        }
    }

    if ($erro === '' && $tem_arquivo) {
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $erro = 'Falha ao enviar o anexo.';
        } elseif ($arquivo['size'] > 5 * 1024 * 1024) {
            $erro = 'O anexo deve ter no máximo 5 MB.';
        } else {
            $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if (!isset($tipos_ok[$ext])) {
                $erro = 'Tipo de arquivo não permitido. Use PNG, JPG, GIF, PDF ou TXT.';
            } elseif (class_exists('finfo')) {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
                if ($mime !== $tipos_ok[$ext]) {
                    $erro = 'O conteúdo do arquivo não corresponde ao tipo informado.';
                }
            }
        }
    }

    // guarda o arquivo antes de criar o chamado
    $nome_salvo = '';
    if ($erro === '' && $tem_arquivo) {
        $pasta = __DIR__ . '/../uploads/';
        if (!is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }
        $nome_salvo = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($arquivo['tmp_name'], $pasta . $nome_salvo)) {
            $erro = 'Não foi possível salvar o anexo.';
            $nome_salvo = '';
        }
    }

    if ($erro === '') {
        $stmt = $pdo->prepare("INSERT INTO chamado (titulo_chamado, descricao_chamado, prioridade_chamado, id_usuario, id_categoria) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$v_titulo, $v_desc, $v_prio, $_SESSION['usuario']['id'], $v_cat]);
        $id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO historico (descricao_historico, id_chamado) VALUES (?, ?)")
            ->execute(['Chamado aberto por ' . $_SESSION['usuario']['nome'], $id]);

        if ($nome_salvo !== '') {
            $pdo->prepare("INSERT INTO anexo (nome_original, nome_arquivo, id_chamado) VALUES (?, ?, ?)")
                ->execute([basename($arquivo['name']), $nome_salvo, $id]);
            $pdo->prepare("INSERT INTO historico (descricao_historico, id_chamado) VALUES (?, ?)")
                ->execute(['Anexo adicionado: ' . basename($arquivo['name']), $id]);
        }

        header('Location: chamado_ver.php?id=' . $id . '&ok=1');
        exit;
    }
}

$titulo = 'Novo chamado';
require '../includes/header.php';
?>
<h1>Abrir chamado</h1>
<?php if ($erro): ?><div class="msg erro"><?= h($erro) ?></div><?php endif; ?>
<?php if (!$categorias): ?>
  <div class="msg erro">Nenhuma categoria cadastrada. Peça ao responsável (admin) para cadastrar uma categoria.</div>
<?php endif; ?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <label>Título (o problema)</label>
    <input type="text" name="titulo" maxlength="150" value="<?= h($v_titulo) ?>">
    <label>Descrição</label>
    <textarea name="descricao"><?= h($v_desc) ?></textarea>
    <div class="linha">
      <div>
        <label>Categoria</label>
        <select name="categoria">
          <option value="">Selecione...</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['id_categoria'] ?>" <?= $v_cat === (int)$c['id_categoria'] ? 'selected' : '' ?>><?= h($c['nome_categoria']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Prioridade</label>
        <select name="prioridade">
          <option value="baixa" <?= $v_prio === 'baixa' ? 'selected' : '' ?>>Baixa</option>
          <option value="media" <?= $v_prio === 'media' ? 'selected' : '' ?>>Média</option>
          <option value="alta" <?= $v_prio === 'alta' ? 'selected' : '' ?>>Alta</option>
        </select>
      </div>
    </div>
    <label>Anexo (print do erro, opcional — PNG, JPG, GIF, PDF ou TXT, até 5 MB)</label>
    <input type="file" name="anexo" accept=".png,.jpg,.jpeg,.gif,.pdf,.txt">
    <button type="submit">Registrar chamado</button>
  </form>
</div>
<?php require '../includes/footer.php'; ?>
