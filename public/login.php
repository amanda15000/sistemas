<?php
require_once '../config/auth.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email_usuario = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    // Modificado: Comparando os textos puros de forma segura contra timing attacks
    if ($u && hash_equals($u['senha_usuario'], $senha)) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => $u['id_usuario'],
            'nome' => $u['nome_usuario'],
            'perfil' => $u['perfil_usuario'],
        ];
        header('Location: painel.php');
        exit;
    }
    $erro = 'E-mail ou senha inválidos.';
}

$titulo = 'Login';
require '../includes/header.php';
?>
<div class="card caixa-login">
  <h1>Entrar</h1>
  <?php if ($erro): ?><div class="msg erro"><?= h($erro) ?></div><?php endif; ?>
  <form method="post">
    <label>E-mail</label>
    <input type="email" name="email" required>
    <label>Senha</label>
    <input type="password" name="senha" required>
    <button type="submit">Entrar</button>
  </form>
  <p>Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
</div>
<?php require '../includes/footer.php'; ?>
