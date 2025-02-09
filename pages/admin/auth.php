<?php
function verificarAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ../admin-login.php');
        exit;
    }
}

function verificarSuperAdmin() {
    if (!isset($_SESSION['admin_nivel']) || $_SESSION['admin_nivel'] !== 'super_admin') {
        header('Location: dashboard.php');
        exit;
    }
}
?>