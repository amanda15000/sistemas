<?php
require_once '../config/auth.php';
exigir_login();

$id = (int)($_GET['id'] ?? 0);
$perfil = perfil_atual();
$erro = '';
$ok = isset($_GET['ok']) ? 'Chamado registrado com sucesso!' : '';

function buscar_chamado($pdo, $id) {
    $stmt = $pdo->prepare("SELECT c.*, cat.nome_categoria, u.nome_usuario, t.nome_usuario AS nome_tecnico
        FROM chamado c
        JOIN categoria cat ON cat.id_categoria = c.id_categoria
        JOIN usuario u ON u.id_usuario = c.id_usuario
        LEFT JOIN usuario t ON t.id_usuario = c.id_tecnico
        WHERE c.id_chamado = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registrar($pdo, $id, $texto) {
    $pdo->prepare("INSERT INTO historico (descricao_historico, id_chamado) VALUES (?, ?)")->execute([$texto, $id]);
}

$eu = (int)$_SESSION['usuario']['id'];
$c = buscar_chamado($pdo, $id);
$sem_acesso = !$c
    || ($perfil === 'usuario' && (int)$c['id_usuario'] !== $eu)
    || ($perfil === 'tecnico' && $c['id_tecnico'] !== null && (int)$c['id_tecnico'] !== $eu);
if ($sem_acesso) {
    header('Location: chamados.php');
    exit;
}

// Status em que o chamado é considerado encerrado (sem novas mensagens).
// Para bloquear também o "resolvido", use: ['resolvido', 'fechado']
$status_encerrados = ['fechado'];

function situacao($c, $perfil, $eu, $status_encerrados) {
    $responsavel = $c['id_tecnico'] !== null && (int)$c['id_tecnico'] === $eu;
    $encerrado = in_array($c['status_chamado'], $status_encerrados);
    // só conversam: o usuário dono do chamado e o atendente responsável
    $pode_comentar = !$encerrado && ($perfil === 'usuario' || $responsavel);
    return [$responsavel, $encerrado, $pode_comentar];
}
[$sou_resp, $encerrado, $pode_comentar] = situacao($c, $perfil, $eu, $status_encerrados);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $quem = $_SESSION['usuario']['nome'];

    if ($acao === 'comentar') {
        $txt = trim($_POST['texto'] ?? '');
        if (!$pode_comentar) {
            $erro = $encerrado
                ? 'Este chamado está encerrado. Não é possível enviar novas mensagens.'
                : 'Você não pode enviar mensagens neste chamado.';
        } elseif ($txt !== '') {
            registrar($pdo, $id, "$quem comentou: $txt");
            $ok = 'Mensagem enviada.';
        }
    } elseif ($acao === 'status' && $sou_resp) {
        $novo = $_POST['status'] ?? '';
        if (in_array($novo, ['aberto', 'em_andamento', 'pendente', 'resolvido', 'fechado'])) {
            $pdo->prepare("UPDATE chamado SET status_chamado = ? WHERE id_chamado = ?")->execute([$novo, $id]);
            registrar($pdo, $id, "$quem alterou o status para " . str_replace('_', ' ', $novo));
            $ok = 'Status atualizado.';
        }
    } elseif ($acao === 'atribuir' && ($perfil === 'admin' || ($perfil === 'tecnico' && (int)$c['id_tecnico'] === $eu))) {
        $tec = (int)($_POST['tecnico'] ?? 0);
        $stmt = $pdo->prepare("SELECT nome_usuario, perfil_usuario FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$tec]);
        $alvo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$alvo || !in_array($alvo['perfil_usuario'], ['tecnico', 'admin'])) {
            $erro = 'Apenas usuários com perfil de Atendente ou Administrador podem assumir chamados.';
        } else {
            $pdo->prepare("UPDATE chamado SET id_tecnico = ? WHERE id_chamado = ?")->execute([$tec, $id]);
            registrar($pdo, $id, "$quem atribuiu o chamado a " . $alvo['nome_usuario']);
            $ok = 'Atendente atribuído.';
        }
    } elseif ($acao === 'assumir' && $perfil === 'tecnico' && $c['id_tecnico'] === null) {
        $pdo->prepare("UPDATE chamado SET id_tecnico = ?, status_chamado = 'em_andamento' WHERE id_chamado = ?")
            ->execute([$_SESSION['usuario']['id'], $id]);
        registrar($pdo, $id, "$quem assumiu o chamado");
        $ok = 'Você assumiu este chamado.';
    }
    $c = buscar_chamado($pdo, $id);
    [$sou_resp, $encerrado, $pode_comentar] = situacao($c, $perfil, $eu, $status_encerrados);
}

$stmt = $pdo->prepare("SELECT * FROM historico WHERE id_chamado = ? ORDER BY data_registro_historico DESC, id_historico DESC");
$stmt->execute([$id]);
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM anexo WHERE id_chamado = ? ORDER BY id_anexo");
$stmt->execute([$id]);
$anexos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$conversa = [];
$eventos = [];
foreach ($historico as $hist) {
    if (preg_match('/^(.+?) comentou: (.*)$/s', $hist['descricao_historico'], $m)) {
        $conversa[] = ['autor' => $m[1], 'texto' => $m[2], 'data' => $hist['data_registro_historico']];
    } else {
        $eventos[] = $hist;
    }
}
$conversa = array_reverse($conversa); // mais antigas primeiro

$tecnicos = $pdo->query("SELECT id_usuario, nome_usuario FROM usuario WHERE perfil_usuario IN ('tecnico','admin') ORDER BY nome_usuario")->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Chamado #' . $id;
require '../includes/header.php';
?>
<h1>Chamado #<?= $c['id_chamado'] ?> — <?= h($c['titulo_chamado']) ?></h1>
<?php if ($erro): ?><div class="msg erro"><?= h($erro) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="msg ok"><?= h($ok) ?></div><?php endif; ?>

<div class="card">
  <p><strong>Descrição:</strong><br><?= nl2br(h($c['descricao_chamado'])) ?></p>
  <p>
    <strong>Categoria:</strong> <?= h($c['nome_categoria']) ?> |
    <strong>Prioridade:</strong> <span class="etiqueta <?= h($c['prioridade_chamado']) ?>"><?= h($c['prioridade_chamado']) ?></span> |
    <strong>Status:</strong> <span class="etiqueta <?= h($c['status_chamado']) ?>"><?= h(str_replace('_', ' ', $c['status_chamado'])) ?></span>
  </p>
  <p>
    <strong>Aberto por:</strong> <?= h($c['nome_usuario']) ?> |
    <strong>Atendente:</strong> <?= h($c['nome_tecnico'] ?? 'Não atribuído') ?> |
    <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($c['data_registro_chamado'])) ?>
  </p>
</div>

<?php if ($anexos): ?>
<div class="card">
  <strong>Anexos</strong>
  <ul class="anexos">
    <?php foreach ($anexos as $an): ?>
      <li><a href="download.php?id=<?= $an['id_anexo'] ?>" target="_blank"><?= h($an['nome_original']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if ($perfil === 'tecnico' && $c['id_tecnico'] === null): ?>
<form method="post"><input type="hidden" name="acao" value="assumir"><button type="submit">Assumir este chamado</button></form>
<?php endif; ?>

<?php if ($perfil === 'admin' || ($perfil === 'tecnico' && (int)$c['id_tecnico'] === $eu)): ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="acao" value="atribuir">
    <label>Atribuir / transferir atendente</label>
    <select name="tecnico">
      <?php foreach ($tecnicos as $t): ?>
        <option value="<?= $t['id_usuario'] ?>" <?= (int)$c['id_tecnico'] === (int)$t['id_usuario'] ? 'selected' : '' ?>><?= h($t['nome_usuario']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Atribuir</button>
  </form>
</div>
<?php endif; ?>

<?php if ($sou_resp): ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="acao" value="status">
    <label>Alterar status</label>
    <select name="status">
      <?php foreach (['aberto' => 'Aberto', 'em_andamento' => 'Em andamento', 'pendente' => 'Pendente', 'resolvido' => 'Resolvido', 'fechado' => 'Fechado'] as $v => $n): ?>
        <option value="<?= $v ?>" <?= $c['status_chamado'] === $v ? 'selected' : '' ?>><?= $n ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Atualizar status</button>
  </form>
</div>
<?php endif; ?>

<h2>Conversa</h2>
<div class="conversa">
  <?php foreach ($conversa as $msg): ?>
    <div class="msg-chat <?= $msg['autor'] === $_SESSION['usuario']['nome'] ? 'minha' : '' ?>">
      <div class="autor"><?= h($msg['autor']) ?> &middot; <?= date('d/m/Y H:i', strtotime($msg['data'])) ?></div>
      <?= nl2br(h($msg['texto'])) ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$conversa): ?><p>Nenhuma mensagem neste chamado.</p><?php endif; ?>
</div>

<?php if ($pode_comentar): ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="acao" value="comentar">
    <label>Responder</label>
    <textarea name="texto" required></textarea>
    <button type="submit">Enviar mensagem</button>
  </form>
</div>
<?php elseif ($encerrado): ?>
<div class="msg erro">Este chamado está encerrado. Não é possível enviar novas mensagens.</div>
<?php else: ?>
<div class="msg">A conversa é feita entre o solicitante e o atendente responsável pelo chamado.</div>
<?php endif; ?>

<h2>Histórico do chamado</h2>
<table>
  <tr><th>Data</th><th>Evento</th></tr>
  <?php foreach ($eventos as $ev): ?>
  <tr>
    <td><?= date('d/m/Y H:i', strtotime($ev['data_registro_historico'])) ?></td>
    <td><?= h($ev['descricao_historico']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<p><a class="btn" href="chamados.php">Voltar</a></p>
<?php require '../includes/footer.php'; ?>
