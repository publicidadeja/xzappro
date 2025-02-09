<?php
session_start();

// Função para verificar se o usuário está logado
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

// Redireciona para a página de login se o usuário não estiver logado
function redirecionarSeNaoLogado() {
    if (!estaLogado()) {
        header('Location: ../pages/login.php');
        exit;
    }
}
?>