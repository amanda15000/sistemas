<?php
require_once '../config/auth.php';
exigir_perfil(['admin']);

$erro = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['acao'] ?? '') === 'criar') {
        $nome   = trim($_POST['nome'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $senha  = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'tecnico';

        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 4 || !in_array($perfil, ['admin', 'tecnico'])) {
            $erro = 'Preencha todos os campos corretamente (senha com pelo menos 4 caracteres).';
        } else {
            try {
                $pdo->prepare("INSERT INTO usuario (nome_usuario, email_usuario, senha_usuario, perfil_usuario) VALUES (?, ?, ?, ?)")
                    ->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil]);
                $ok = 'Usuário cadastrado!';
            } catch (PDOException $e) {
                $erro = 'Este e-mail já está cadastrado.';
            }
        }
    } elseif (($_POST['acao'] ?? '') === 'excluir') {
        $id = (int)$_POST['id'];
        if ($id === (int)$_SESSION['usuario']['id']) {
            $erro = 'Você não pode excluir o seu próprio usuário.';
        } else {
            try {
                $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$id]);
                $ok = 'Usuário excluído.';
            } catch (PDOException $e) {
                $erro = 'Não é possível excluir: o usuário possui chamados vinculados.';
            }
        }
    }
}

$lista = $pdo->query("SELECT * FROM usuario ORDER BY nome_usuario")->fetchAll(PDO::FETCH_ASSOC);
$nomes = ['admin' => 'Responsável (admin)', 'tecnico' => 'Atendente (técnico)', 'usuario' => 'Usuário'];

$titulo = 'Usuários';
require '../includes/header.php';
?>
<h1>Usuários</h1>
<?php if ($erro): ?><div class="msg erro"><?= h($erro) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="msg ok"><?= h($ok) ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <input type="hidden" name="acao" value="criar">
    <div class="linha">
      <div><label>Nome</label><input type="text" name="nome" required></div>
      <div><label>E-mail</label><input type="email" name="email" required></div>
    </div>
    <div class="linha">
      <div><label>Senha</label><input type="password" name="senha" required></div>
      <div>
        <label>Perfil</label>
        <select name="perfil">
          <option value="tecnico" selected>Atendente (técnico)</option>
          <option value="admin">Responsável (admin)</option>
        </select>
      </div>
    </div>
    <button type="submit">Cadastrar usuário</button>
  </form>
</div>

<table>
  <tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Ações</th></tr>
  <?php foreach ($lista as $u): ?>
  <tr>
    <td><?= $u['id_usuario'] ?></td>
    <td><?= h($u['nome_usuario']) ?></td>
    <td><?= h($u['email_usuario']) ?></td>
    <td><?= h($nomes[$u['perfil_usuario']] ?? $u['perfil_usuario']) ?></td>
    <td>
      <form method="post" onsubmit="return confirm('Excluir este usuário?')">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
        <button class="btn-perigo btn-pequeno" type="submit">Excluir</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php require '../includes/footer.php'; ?>