<?php
require_once '../config/auth.php';
exigir_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT a.*, c.id_usuario, c.id_tecnico
                       FROM anexo a JOIN chamado c ON c.id_chamado = a.id_chamado
                       WHERE a.id_anexo = ?");
$stmt->execute([$id]);
$a = $stmt->fetch(PDO::FETCH_ASSOC);

$perfil = perfil_atual();
$eu = (int)$_SESSION['usuario']['id'];
$permitido = $a && (
    $perfil === 'admin'
    || ($perfil === 'usuario' && (int)$a['id_usuario'] === $eu)
    || ($perfil === 'tecnico' && ($a['id_tecnico'] === null || (int)$a['id_tecnico'] === $eu))
);

if (!$permitido) {
    http_response_code(403);
    exit('Acesso negado.');
}

$caminho = __DIR__ . '/../uploads/' . basename($a['nome_arquivo']);
if (!is_file($caminho)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$ext = strtolower(pathinfo($a['nome_arquivo'], PATHINFO_EXTENSION));
$tipos = [
    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'gif' => 'image/gif', 'pdf' => 'application/pdf', 'txt' => 'text/plain; charset=utf-8',
];
$mime = $tipos[$ext] ?? 'application/octet-stream';
$inline = $ext !== 'txt' && isset($tipos[$ext]);

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($a['nome_original']));
header('Content-Length: ' . filesize($caminho));
readfile($caminho);
