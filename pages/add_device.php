<?php
// add_device.php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirecionarSeNaoLogado();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Validar entrada
    $nome = trim($_POST['nome'] ?? '');
    if (empty($nome)) {
        throw new Exception('Nome do dispositivo é obrigatório');
    }

    // Gerar device_id único
    $device_id = 'device_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', uniqid());

    // Inserir novo dispositivo
    $stmt = $pdo->prepare("
        INSERT INTO dispositivos (usuario_id, nome, device_id, status) 
        VALUES (?, ?, ?, 'WAITING_QR')
    ");

    $success = $stmt->execute([
        $_SESSION['usuario_id'],
        $nome,
        $device_id
    ]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Dispositivo adicionado com sucesso',
            'device_id' => $device_id
        ]);
    } else {
        throw new Exception('Erro ao adicionar dispositivo');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}