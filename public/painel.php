<?php
require_once '../config/auth.php';
exigir_login();

$perfil = perfil_atual();
$eu = (int)$_SESSION['usuario']['id'];

function contar($pdo, $where, $params = []) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chamado WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

if ($perfil === 'admin') {
    $subtitulo = 'Visão geral de todo o sistema';
    $cards = [
        'Abertos'          => contar($pdo, "status_chamado = 'aberto'"),
        'Em andamento'     => contar($pdo, "status_chamado = 'em_andamento'"),
        'Pendentes'        => contar($pdo, "status_chamado = 'pendente'"),
        'Resolvidos'       => contar($pdo, "status_chamado = 'resolvido'"),
        'Fechados'         => contar($pdo, "status_chamado = 'fechado'"),
        'Sem atendente'    => contar($pdo, "id_tecnico IS NULL AND status_chamado <> 'fechado'"),
        'Usuários'         => (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn(),
    ];
} elseif ($perfil === 'tecnico') {
    $subtitulo = 'Seus atendimentos';
    $cards = [
        'Na fila (sem atendente)' => contar($pdo, "id_tecnico IS NULL AND status_chamado = 'aberto'"),
        'Meus em andamento'       => contar($pdo, "id_tecnico = ? AND status_chamado = 'em_andamento'", [$eu]),
        'Meus resolvidos'         => contar($pdo, "id_tecnico = ? AND status_chamado = 'resolvido'", [$eu]),
        'Total meu'               => contar($pdo, "id_tecnico = ?", [$eu]),
    ];
} else {
    $subtitulo = 'Seus chamados';
    $cards = [
        'Abertos'      => contar($pdo, "id_usuario = ? AND status_chamado = 'aberto'", [$eu]),
        'Em andamento' => contar($pdo, "id_usuario = ? AND status_chamado = 'em_andamento'", [$eu]),
        'Pendentes'    => contar($pdo, "id_usuario = ? AND status_chamado = 'pendente'", [$eu]),
        'Resolvidos'   => contar($pdo, "id_usuario = ? AND status_chamado = 'resolvido'", [$eu]),
        'Fechados'     => contar($pdo, "id_usuario = ? AND status_chamado = 'fechado'", [$eu]),
    ];
}

$titulo = 'Painel';
require '../includes/header.php';
?>
<h1>Painel</h1>
<p>Bem-vindo(a), <strong><?= h($_SESSION['usuario']['nome']) ?></strong>. <?= h($subtitulo) ?>.</p>
<div class="numeros">
  <?php foreach ($cards as $nome => $n): ?>
    <div class="card"><div class="n"><?= $n ?></div><?= h($nome) ?></div>
  <?php endforeach; ?>
</div>

<?php if ($perfil === 'tecnico'): ?>
  <a class="btn" href="chamados.php?ver=fila">Ver fila de chamados</a>
  <a class="btn" href="chamados.php?ver=meus">Meus atendimentos</a>
<?php elseif ($perfil === 'admin'): ?>
  <a class="btn" href="chamados.php">Todos os chamados</a>
  <a class="btn" href="usuarios.php">Gerenciar usuários</a>
<?php else: ?>
  <a class="btn" href="chamado_novo.php">Abrir novo chamado</a>
  <a class="btn" href="chamados.php">Meus chamados</a>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>
