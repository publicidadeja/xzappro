<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/marketing-assistant.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data['action'] === 'get_marketing_advice') {
        $assistant = new MarketingAssistant('SUA_API_AQUI');
        $response = $assistant->getMarketingAdvice($data['question']);
        
        echo json_encode([
            'success' => true,
            'response' => $response['content'][0]['text']
        ]);
    }
}
?>