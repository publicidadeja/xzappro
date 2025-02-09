<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirecionarSeNaoLogado();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do dispositivo não fornecido']);
    exit;
}

try {
    // Verificar se o dispositivo pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT * FROM dispositivos 
        WHERE id = ? AND usuario_id = ?
    ");
    
    $stmt->execute([$_GET['id'], $_SESSION['usuario_id']]);
    $dispositivo = $stmt->fetch();

    if (!$dispositivo) {
        throw new Exception('Dispositivo não encontrado');
    }

    // Excluir dispositivo
    $stmt = $pdo->prepare("DELETE FROM dispositivos WHERE id = ?");
    $success = $stmt->execute([$_GET['id']]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Dispositivo excluído com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao excluir dispositivo');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}