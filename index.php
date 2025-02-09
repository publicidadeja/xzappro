<?php
session_start();

// Verifica se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: pages/dashboard.php');
    exit;
} else {
    header('Location: pages/login.php');
    exit;
}
?>