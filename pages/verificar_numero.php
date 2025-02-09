<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

if (!isset($_SESSION['usuario_id'])) {
    exit(json_encode(['erro' => 'Não autorizado']));
}

if (isset($_POST['numero'])) {
    $numero = preg_replace('/[^0-9]/', '', $_POST['numero']);
    
    // Add Brazilian country code if not present
    if (strlen($numero) == 10 || strlen($numero) == 11) {
        $numero = '55' . $numero;
    }
    
    $lead_existente = verificarNumeroExistente($pdo, $numero, $_SESSION['usuario_id']);
    
    echo json_encode([
        'existe' => !empty($lead_existente),
        'nome' => $lead_existente ? htmlspecialchars($lead_existente['nome']) : ''
    ]);
}
?>