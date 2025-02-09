<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    header('Content-Type: application/json'); // Define o header Content-Type
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

// Inclua as bibliotecas necessárias para fazer a chamada à API da Anthropic
// (Você precisará instalar a biblioteca adequada, como Guzzle)
require 'vendor/autoload.php'; // Ajuste o caminho se necessário

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Chave da API da Anthropic (substitua pela sua chave real)
$api_key = 'SUA_API_AQUI';

// Endpoint da API da Anthropic (Claude 3.5 Haiku)
$api_url = 'https://api.anthropic.com/v1/messages'; // Verifique a documentação da Anthropic para o endpoint correto

// Verifique se o prompt foi enviado
if (isset($_POST['prompt'])) {
    $prompt = $_POST['prompt'];

    // Construa a mensagem para a API da Anthropic
    $message = "Você é um assistente de marketing especializado em criar mensagens persuasivas para WhatsApp.  O usuário descreverá o que ele quer enviar e você criará uma mensagem concisa e atraente para WhatsApp, usando técnicas de copywriting.  Aqui está a descrição do usuário: " . $prompt;

    try {
        // Crie um cliente Guzzle
        $client = new Client();

        // Faça a chamada à API da Anthropic
        $response = $client->post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'Anthropic-Version' => '2023-06-01' // Verifique a versão mais recente na documentação
            ],
            'json' => [
                'model' => 'claude-3.5-haiku-20240620', // Use o modelo correto
                'max_tokens' => 200, // Ajuste conforme necessário
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ]
            ]
        ]);

        // Obtenha o corpo da resposta
        $body = (string) $response->getBody();
        error_log('Resposta da API Anthropic: ' . $body); // Registra a resposta completa

        $data = json_decode($body, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            // Houve um erro ao decodificar o JSON
            $error_message = 'Erro ao decodificar JSON: ' . json_last_error_msg();
            error_log($error_message);
            http_response_code(500);
            header('Content-Type: application/json'); // Define o header Content-Type
            echo json_encode(['success' => false, 'error' => $error_message]);
            exit;
        }

        // Extraia a mensagem da resposta (verifique a estrutura da resposta da API)
        // Adapte este código à estrutura real da resposta da API
        $ai_message = '';
        if (isset($data['content']) && is_array($data['content']) && count($data['content']) > 0) {
            foreach ($data['content'] as $content_item) {
                if (isset($content_item['type']) && $content_item['type'] === 'text' && isset($content_item['text'])) {
                    $ai_message .= $content_item['text'];
                }
            }
        } else {
            $ai_message = 'Não foi possível gerar uma sugestão. Estrutura de resposta inesperada.';
            error_log('Estrutura de resposta da API Anthropic inesperada: ' . $body);
        }

        // Envie a resposta de volta para o cliente
        header('Content-Type: application/json'); // Define o header Content-Type
        echo json_encode(['success' => true, 'message' => $ai_message]);

    } catch (RequestException $e) {
        // Trata erros de requisição (ex: timeout, conexão recusada)
        $error_message = 'Erro na requisição à API da Anthropic: ' . $e->getMessage();
        error_log($error_message);
        http_response_code(500);
        header('Content-Type: application/json'); // Define o header Content-Type
        echo json_encode(['success' => false, 'error' => $error_message]);

    } catch (Exception $e) {
        // Em caso de erro, registre e envie uma mensagem de erro
        $error_message = 'Erro geral na chamada à API da Anthropic: ' . $e->getMessage();
        error_log($error_message);
        http_response_code(500);
        header('Content-Type: application/json'); // Define o header Content-Type
        echo json_encode(['success' => false, 'error' => $error_message]);
    }

} else {
    // Se o prompt não foi enviado, retorne um erro
    http_response_code(400);
    header('Content-Type: application/json'); // Define o header Content-Type
    echo json_encode(['success' => false, 'error' => 'Prompt não fornecido.']);
}
?>