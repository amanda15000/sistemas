<?php
require_once '../config/auth.php';
exigir_perfil(['admin']);

$erro = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['acao'] ?? '') === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $erro = 'Informe o nome da categoria.';
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categoria WHERE LOWER(nome_categoria) = LOWER(?)");
            $stmt->execute([$nome]);
            if ($stmt->fetchColumn() > 0) {
                $erro = 'Já existe uma categoria com esse nome.';
            } else {
                try {
                    $pdo->prepare("INSERT INTO categoria (nome_categoria) VALUES (?)")->execute([$nome]);
                    $ok = 'Categoria cadastrada!';
                } catch (PDOException $e) {
                    $erro = 'Já existe uma categoria com esse nome.';
                }
            }
        }
    } elseif (($_POST['acao'] ?? '') === 'excluir') {
        try {
            $pdo->prepare("DELETE FROM categoria WHERE id_categoria = ?")->execute([(int)$_POST['id']]);
            $ok = 'Categoria excluída.';
        } catch (PDOException $e) {
            $erro = 'Não é possível excluir: existem chamados nessa categoria.';
        }
    }
}

$lista = $pdo->query("SELECT * FROM categoria ORDER BY nome_categoria")->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Categorias';
require '../includes/header.php';
?>
<h1>Categorias</h1>
<?php if ($erro): ?><div class="msg erro"><?= h($erro) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="msg ok"><?= h($ok) ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <input type="hidden" name="acao" value="criar">
    <label>Nome da categoria</label>
    <input type="text" name="nome" required>
    <button type="submit">Salvar</button>
  </form>
</div>

<table>
  <tr><th>ID</th><th>Nome</th><th>Ações</th></tr>
  <?php foreach ($lista as $c): ?>
  <tr>
    <td><?= $c['id_categoria'] ?></td>
    <td><?= h($c['nome_categoria']) ?></td>
    <td>
      <form method="post" onsubmit="return confirm('Excluir esta categoria?')">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" value="<?= $c['id_categoria'] ?>">
        <button class="btn-perigo btn-pequeno" type="submit">Excluir</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$lista): ?><tr><td colspan="3">Nenhuma categoria cadastrada.</td></tr><?php endif; ?>
</table>
<?php require '../includes/footer.php'; ?>
