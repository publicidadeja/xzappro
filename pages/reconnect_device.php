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
        SELECT d.*, u.token_dispositivo 
        FROM dispositivos d 
        JOIN usuarios u ON u.id = d.usuario_id 
        WHERE d.id = ? AND d.usuario_id = ?
    ");
    
    $stmt->execute([$_GET['id'], $_SESSION['usuario_id']]);
    $dispositivo = $stmt->fetch();

    if (!$dispositivo) {
        throw new Exception('Dispositivo não encontrado');
    }

    // Atualizar status do dispositivo
    $stmt = $pdo->prepare("UPDATE dispositivos SET status = 'INITIALIZING' WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // Chamar API para reconectar
    $url = 'https://api2.publicidadeja.com.br/api/devices/reconnect';
    $headers = [
        'Authorization: Bearer ' . $dispositivo['token_dispositivo'],
        'Content-Type: application/json'
    ];

    $data = json_encode([
        'device_id' => $dispositivo['device_id']
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        echo json_encode([
            'success' => true,
            'message' => 'Dispositivo reconectado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao reconectar dispositivo');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}