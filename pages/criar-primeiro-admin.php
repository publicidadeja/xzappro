<?php
include '../includes/db.php';

// Dados do primeiro administrador
$nome = "Administrador";
$email = "admin@exemplo.com";
$senha = "senha123"; // Mude para uma senha segura
$nivel_acesso = "super_admin";

try {
    // Verifica se já existe algum admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM administradores");
    if ($stmt->fetchColumn() > 0) {
        die("Já existe pelo menos um administrador cadastrado.");
    }

    // Cria o hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Insere o administrador
    $stmt = $pdo->prepare("INSERT INTO administradores (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $email, $senha_hash, $nivel_acesso]);

    echo "Administrador criado com sucesso!";
    
} catch (PDOException $e) {
    die("Erro ao criar administrador: " . $e->getMessage());
}
?>