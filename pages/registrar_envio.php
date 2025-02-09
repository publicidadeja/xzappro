<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $lead_id = $_POST['lead_id'];
    $dispositivo_id = $_POST['dispositivo_id'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE leads_enviados SET 
                              status = ?, 
                              data_envio = NOW(), 
                              dispositivo_id = ? 
                              WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$status, $dispositivo_id, $lead_id, $_SESSION['usuario_id']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}