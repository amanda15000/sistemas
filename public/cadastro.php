<?php
require_once '../config/auth.php';

$erro = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 4) {
        $erro = 'Preencha nome, um e-mail válido e senha com pelo menos 4 caracteres.';
    } else {
        // O primeiro usuário do sistema vira admin. Os demais são "usuario".
        $total = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
        $perfil = $total === 0 ? 'admin' : 'usuario';

        try {
            $stmt = $pdo->prepare("INSERT INTO usuario (nome_usuario, email_usuario, senha_usuario, perfil_usuario) VALUES (?, ?, ?, ?)");
            
            // Criptografa a senha antes de salvar no banco
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt->execute([$nome, $email, $senhaHash, $perfil]);
            
            $ok = 'Cadastro realizado! Perfil: ' . $perfil . '. Já pode fazer login.';
        } catch (PDOException $e) {
            $erro = 'Este e-mail já está cadastrado.';
        }
    }
}

$titulo = 'Cadastro';
require '../includes/header.php';
?>

<div class="card caixa-login">
  <h1>Criar conta</h1>
  <?php if ($erro): ?><div class="msg erro"><?= h($erro) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="msg ok"><?= h($ok) ?></div><?php endif; ?>
  <form method="post">
    <label>Nome</label>
    <input type="text" name="nome" required>
    <label>E-mail</label>
    <input type="email" name="email" required>
    <label>Senha</label>
    <input type="password" name="senha" required>
    <button type="submit">Cadastrar</button>
  </form>
  <p><a href="login.php">Voltar para o login</a></p>
</div>

<?php require '../includes/footer.php'; ?>