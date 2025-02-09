<?php
include '../includes/db.php';

try {
    // Modificar a estrutura da tabela
    $pdo->exec("ALTER TABLE administradores
        MODIFY id int(11) NOT NULL AUTO_INCREMENT,
        MODIFY nome varchar(100) NOT NULL,
        MODIFY email varchar(100) NOT NULL,
        MODIFY senha varchar(255) NOT NULL,
        MODIFY nivel_acesso enum('admin','super_admin') DEFAULT 'admin',
        MODIFY created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

    // Tentar adicionar as chaves (ignorará se já existirem)
    try {
        $pdo->exec("ALTER TABLE administradores
            ADD PRIMARY KEY (id)");
    } catch (PDOException $e) {
        // Ignora erro se a chave primária já existir
    }

    try {
        $pdo->exec("ALTER TABLE administradores
            ADD UNIQUE KEY (email)");
    } catch (PDOException $e) {
        // Ignora erro se a chave única já existir
    }

    echo "Tabela administradores atualizada com sucesso!";

} catch (PDOException $e) {
    echo "Erro ao atualizar tabela: " . $e->getMessage();
}
?>