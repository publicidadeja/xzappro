<?php
// Função para validar número de telefone (DDI + DDD + número)
function validarNumeroTelefone($numero) {
    // Remove todos os caracteres não numéricos
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Verifica se o número tem 10 ou 11 dígitos (sem o código do país)
    $tamanho = strlen($numero);
    return ($tamanho == 10 || $tamanho == 11);
}

// Função para formatar data no padrão brasileiro (dd/mm/yyyy hh:mm:ss)
function formatarData($data) {
    return date('d/m/Y H:i:s', strtotime($data));
}

function verificarNumeroExistente($pdo, $numero, $usuario_id) {
    // Ensure consistent number format
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Add Brazilian country code if not present
    if (strlen($numero) == 10 || strlen($numero) == 11) {
        $numero = '55' . $numero;
    }
    
    $stmt = $pdo->prepare("SELECT nome FROM leads_enviados WHERE numero = ? AND usuario_id = ? LIMIT 1");
    $stmt->execute([$numero, $usuario_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>