<?php
require_once '../config/auth.php';
exigir_login();

$perfil = perfil_atual();
$eu = (int)$_SESSION['usuario']['id'];
$ver = $_GET['ver'] ?? '';

$status_validos = [
    'aberto' => 'Aberto', 'em_andamento' => 'Em andamento', 'pendente' => 'Pendente',
    'resolvido' => 'Resolvido', 'fechado' => 'Fechado',
];
$f_id = trim($_GET['id'] ?? '');
$f_status = $_GET['status'] ?? '';
$f_cat = (int)($_GET['categoria'] ?? 0);
if (!isset($status_validos[$f_status])) {
    $f_status = '';
}

$where = [];
$params = [];

// escopo de cada perfil
if ($perfil === 'usuario') {
    $titulo = 'Meus chamados';
    $where[] = 'c.id_usuario = ?';
    $params[] = $eu;
} elseif ($perfil === 'tecnico') {
    if ($ver === 'fila') {
        $titulo = 'Fila de chamados (sem atendente)';
        $where[] = "c.id_tecnico IS NULL AND c.status_chamado = 'aberto'";
    } else {
        $ver = 'meus';
        $titulo = 'Meus atendimentos';
        $where[] = 'c.id_tecnico = ?';
        $params[] = $eu;
    }
} else {
    $titulo = 'Todos os chamados';
}

// filtros (RF02): ID, status e categoria
if ($f_id !== '' && ctype_digit($f_id)) {
    $where[] = 'c.id_chamado = ?';
    $params[] = (int)$f_id;
}
if ($f_status !== '') {
    $where[] = 'c.status_chamado = ?';
    $params[] = $f_status;
}
if ($f_cat > 0) {
    $where[] = 'c.id_categoria = ?';
    $params[] = $f_cat;
}

$sql = "SELECT c.*, cat.nome_categoria, u.nome_usuario, t.nome_usuario AS nome_tecnico,
        (SELECT h.descricao_historico FROM historico h
          WHERE h.id_chamado = c.id_chamado AND h.descricao_historico LIKE '% comentou: %'
          ORDER BY h.id_historico DESC LIMIT 1) AS ultimo_comentario
        FROM chamado c
        JOIN categoria cat ON cat.id_categoria = c.id_categoria
        JOIN usuario u ON u.id_usuario = c.id_usuario
        LEFT JOIN usuario t ON t.id_usuario = c.id_tecnico";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY c.id_chamado DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = $pdo->query("SELECT * FROM categoria ORDER BY nome_categoria")->fetchAll(PDO::FETCH_ASSOC);
$link_limpar = 'chamados.php' . ($perfil === 'tecnico' ? '?ver=' . $ver : '');

require '../includes/header.php';
?>
<h1><?= h($titulo) ?></h1>

<?php if ($perfil === 'tecnico'): ?>
<div class="abas">
  <a href="chamados.php?ver=fila" class="<?= $ver === 'fila' ? 'ativa' : '' ?>">Fila</a>
  <a href="chamados.php?ver=meus" class="<?= $ver === 'meus' ? 'ativa' : '' ?>">Meus atendimentos</a>
</div>
<?php endif; ?>

<div class="card">
  <form method="get">
    <?php if ($perfil === 'tecnico'): ?><input type="hidden" name="ver" value="<?= h($ver) ?>"><?php endif; ?>
    <div class="linha">
      <div>
        <label>Nº do chamado</label>
        <input type="number" name="id" min="1" value="<?= h($f_id) ?>">
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <option value="">Todos</option>
          <?php foreach ($status_validos as $v => $n): ?>
            <option value="<?= $v ?>" <?= $f_status === $v ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Categoria</label>
        <select name="categoria">
          <option value="0">Todas</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id_categoria'] ?>" <?= $f_cat === (int)$cat['id_categoria'] ? 'selected' : '' ?>><?= h($cat['nome_categoria']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button type="submit">Filtrar</button>
    <a class="btn btn-claro" href="<?= h($link_limpar) ?>">Limpar</a>
  </form>
</div>

<table>
  <tr><th>#</th><th>Título</th><th>Categoria</th><th>Prioridade</th><th>Status</th><th>Aberto por</th><th>Atendente</th><th>Última mensagem</th><th></th></tr>
  <?php foreach ($lista as $c): ?>
  <tr>
    <td><?= $c['id_chamado'] ?></td>
    <td><?= h($c['titulo_chamado']) ?></td>
    <td><?= h($c['nome_categoria']) ?></td>
    <td><span class="etiqueta <?= h($c['prioridade_chamado']) ?>"><?= h($c['prioridade_chamado']) ?></span></td>
    <td><span class="etiqueta <?= h($c['status_chamado']) ?>"><?= h(str_replace('_', ' ', $c['status_chamado'])) ?></span></td>
    <td><?= h($c['nome_usuario']) ?></td>
    <td><?= h($c['nome_tecnico'] ?? '—') ?></td>
    <td><?= $c['ultimo_comentario'] ? h(mb_strimwidth(preg_replace('/ comentou: /', ': ', $c['ultimo_comentario'], 1), 0, 60, '…')) : '—' ?></td>
    <td><a class="btn btn-pequeno" href="chamado_ver.php?id=<?= $c['id_chamado'] ?>">Abrir</a></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$lista): ?><tr><td colspan="9">Nenhum chamado encontrado.</td></tr><?php endif; ?>
</table>
<?php require '../includes/footer.php'; ?>
