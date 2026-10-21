<?php
session_start();
header('Location: ' . (empty($_SESSION['usuario']) ? 'login.php' : 'painel.php'));
exit;
