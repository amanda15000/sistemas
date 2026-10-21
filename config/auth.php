<?php
session_start();
require_once __DIR__ . '/conexao.php';

function h($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

function exigir_login() {
    if (empty($_SESSION['usuario'])) {
        header('Location: login.php');
        exit;
    }
}

function exigir_perfil(array $perfis) {
    exigir_login();
    if (!in_array($_SESSION['usuario']['perfil'], $perfis)) {
        header('Location: painel.php');
        exit;
    }
}

function perfil_atual() {
    return $_SESSION['usuario']['perfil'] ?? '';
}
