<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    // Consultar status da fila
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'ENVIADO' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'PENDENTE' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'ERRO' THEN 1 ELSE 0 END) as failed
        FROM fila_mensagens 
        WHERE usuario_id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $total = $result['total'] ?: 0;
    $sent = $result['sent'] ?: 0;
    $pending = $result['pending'] ?: 0;
    $failed = $result['failed'] ?: 0;

    // Calcular progresso
    $progress = $total > 0 ? round(($sent / $total) * 100) : 0;
    $status = $pending > 0 ? 'processing' : 'completed';

    echo json_encode([
        'status' => $status,
        'progress' => $progress,
        'pending' => $pending,
        'sent' => $sent,
        'failed' => $failed
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}