<?php
session_start();

function estaLogadoComoAdmin() {
    return isset($_SESSION['admin_id']);
}

function redirecionarSeNaoAdmin() {
    if (!estaLogadoComoAdmin()) {
        header('Location: ../admin-login.php');
        exit;
    }
}

function verificarNivelAcesso($nivel_requerido = 'admin') {
    if (!estaLogadoComoAdmin()) {
        return false;
    }
    
    if ($nivel_requerido == 'super_admin' && $_SESSION['admin_nivel'] != 'super_admin') {
        return false;
    }
    
    return true;
}
?>