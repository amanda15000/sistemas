<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../config/conexao.php';

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

// API REST: leitura das categorias (o cadastro é feito pela tela public/categorias.php)
$stmt = $pdo->query("SELECT * FROM categoria ORDER BY nome_categoria");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
