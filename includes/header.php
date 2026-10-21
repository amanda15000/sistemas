<?php
$p = $_SESSION['usuario']['perfil'] ?? '';
$rotulos = ['admin' => 'Responsável (admin)', 'tecnico' => 'Atendente', 'usuario' => 'Usuário'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($titulo ?? 'Sistema de Chamados') ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php if (!empty($_SESSION['usuario'])): ?>
<nav class="menu <?= h($p) ?>">
  <span class="logo">Chamados</span>
  <a href="painel.php">Painel</a>

  <?php if ($p === 'admin'): ?>
    <a href="chamados.php">Todos os chamados</a>
    <a href="categorias.php">Categorias</a>
    <a href="usuarios.php">Usuários</a>
  <?php elseif ($p === 'tecnico'): ?>
    <a href="chamados.php?ver=fila">Fila de chamados</a>
    <a href="chamados.php?ver=meus">Meus atendimentos</a>
  <?php else: ?>
    <a href="chamados.php">Meus chamados</a>
    <a href="chamado_novo.php">Abrir chamado</a>
  <?php endif; ?>

  <span class="espaco"></span>
  <span class="quem"><?= h($_SESSION['usuario']['nome']) ?></span>
  <span class="perfil-badge"><?= h($rotulos[$p] ?? $p) ?></span>
  <a href="logout.php">Sair</a>
</nav>
<?php endif; ?>
<main class="container">
