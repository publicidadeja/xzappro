<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit('Acesso negado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lead_id = $_POST['lead_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($lead_id && $status) {
        try {
            $stmt = $pdo->prepare("UPDATE leads_enviados SET status = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$status, $lead_id, $_SESSION['usuario_id']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}