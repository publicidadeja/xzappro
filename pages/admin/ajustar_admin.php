<?php
include '../includes/db.php';

try {
    // Modificar a coluna nivel_acesso para NOT NULL
    $pdo->exec("ALTER TABLE administradores
                MODIFY nivel_acesso enum('admin','super_admin') NOT NULL DEFAULT 'admin'");
    
    echo "Tabela administradores ajustada com sucesso!";

} catch (PDOException $e) {
    echo "Erro ao ajustar tabela: " . $e->getMessage();
}
?>