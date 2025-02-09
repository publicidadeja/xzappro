<?php
// Configurações iniciais
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuração de log
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/claude_error.log');

// Função para logging
function logError($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "$timestamp - $message\n";
    
    if ($data !== null) {
        $logMessage .= "Data: " . print_r($data, true) . "\n";
    }
    
    $logMessage .= "\n";
    error_log($logMessage);
}

// Tratamento de OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chave API
$CLAUDE_API_KEY = 'MINHAS_CHAVE_AQUI';

// Função para extrair conteúdo da mensagem
function extractMessageContent($response_data) {
    if (!isset($response_data['content']) || !is_array($response_data['content'])) {
        throw new Exception('Formato de resposta inválido: content ausente ou não é array');
    }

    foreach ($response_data['content'] as $content) {
        if (!isset($content['type']) || !isset($content['text'])) {
            continue;
        }

        if ($content['type'] === 'text') {
            return $content['text'];
        }
    }

    throw new Exception('Nenhum conteúdo de texto encontrado na resposta');
}

try {
    // Log do início da requisição
    logError("Nova requisição iniciada");

    // Lê o input
    $raw_input = file_get_contents('php://input');
    logError("Input recebido", $raw_input);

    if (empty($raw_input)) {
        throw new Exception('Input vazio');
    }

    // Decodifica JSON
    $data = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    // Verifica prompt
    if (empty($data['prompt'])) {
        throw new Exception('Prompt não fornecido');
    }

    // Prepara dados para API
    $request_body = json_encode([
        'model' => 'claude-3-haiku-20240307',
        'messages' => [
            [
                'role' => 'user',
                'content' => $data['prompt']
            ]
        ],
        'max_tokens' => 1000
    ]);

    logError("Request para API", $request_body);

    // Configuração cURL
    $curl = curl_init();
    if (!$curl) {
        throw new Exception('Falha ao inicializar cURL');
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.anthropic.com/v1/messages',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $request_body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01',
            'x-api-key: ' . $CLAUDE_API_KEY
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_VERBOSE => true
    ]);

    // Buffer para log verbose do cURL
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($curl, CURLOPT_STDERR, $verbose);

    // Executa request
    $response = curl_exec($curl);
    $curl_errno = curl_errno($curl);
    $curl_error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // Log da resposta cURL
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    fclose($verbose);
    logError("Verbose cURL log", $verbose_log);

    // Log da resposta
    logError("Resposta API (HTTP $http_code)", $response);

    if ($curl_errno) {
        throw new Exception("Erro cURL ($curl_errno): $curl_error");
    }

    curl_close($curl);

    if ($http_code !== 200) {
        $error_data = json_decode($response, true);
        $error_message = isset($error_data['error']['message']) 
            ? $error_data['error']['message'] 
            : "HTTP Error: $http_code";
        throw new Exception($error_message);
    }

    // Processa resposta
    $response_data = json_decode($response, true);
    if (!$response_data) {
        throw new Exception('Falha ao decodificar resposta: ' . json_last_error_msg());
    }

    logError("Resposta decodificada", $response_data);

    // Extrai conteúdo usando a função helper
    try {
        $message_content = extractMessageContent($response_data);
        logError("Conteúdo extraído", [
            'content' => $message_content,
            'length' => strlen($message_content)
        ]);
    } catch (Exception $e) {
        logError("Erro ao extrair conteúdo", $e->getMessage());
        throw $e;
    }

    // Retorna sucesso
    echo json_encode([
        'success' => true,
        'content' => $message_content
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    logError("Erro: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Garante que nada mais será enviado
exit;