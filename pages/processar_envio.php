<?php
session_start();
include '../includes/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); // Forbidden
    echo "Acesso negado.";
    exit;
}

// Garante que a execução não exceda um tempo limite razoável
set_time_limit(300); // 5 minutos

// Função para enviar a mensagem (adaptada do código anterior)
function send_message($token, $numero, $mensagem, $arquivo_path = '') {
    $url = 'https://api2.publicidadeja.com.br/api/messages/send';

    if (!empty($arquivo_path) && file_exists($arquivo_path)) {
        // Envia a mídia primeiro
        $arquivo_nome = preg_replace('/[^a-zA-Z0-9\.]/', '', basename($arquivo_path));
        $cfile = new CURLFile($arquivo_path, mime_content_type($arquivo_path), $arquivo_nome);

        $post_data_media = [
            'number' => $numero,
            'medias' => $cfile
        ];

        $headers_media = [
            'Authorization: Bearer ' . $token,
            'Content-Type: multipart/form-data'
        ];

        $ch_media = curl_init();
        curl_setopt($ch_media, CURLOPT_URL, $url);
        curl_setopt($ch_media, CURLOPT_POST, true);
        curl_setopt($ch_media, CURLOPT_HTTPHEADER, $headers_media);
        curl_setopt($ch_media, CURLOPT_POSTFIELDS, $post_data_media);
        curl_setopt($ch_media, CURLOPT_RETURNTRANSFER, true);

        $response_media = curl_exec($ch_media);
        $http_code_media = curl_getinfo($ch_media, CURLINFO_HTTP_CODE);
        $error_media = curl_error($ch_media);
        curl_close($ch_media);

        if ($http_code_media == 200) {
            // Envia o texto depois
            $headers_text = [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ];

            $data_text = json_encode(['number' => $numero, 'body' => $mensagem], JSON_UNESCAPED_UNICODE);

            $ch_text = curl_init();
            curl_setopt($ch_text, CURLOPT_URL, $url);
            curl_setopt($ch_text, CURLOPT_POST, true);
            curl_setopt($ch_text, CURLOPT_HTTPHEADER, $headers_text);
            curl_setopt($ch_text, CURLOPT_POSTFIELDS, $data_text);
            curl_setopt($ch_text, CURLOPT_RETURNTRANSFER, true);

            $response_text = curl_exec($ch_text);
            $http_code_text = curl_getinfo($ch_text, CURLINFO_HTTP_CODE);
            $error_text = curl_error($ch_text);
            curl_close($ch_text);

            if ($http_code_text == 200) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => "Erro ao enviar mensagem de texto. Código HTTP: " . $http_code_text . ", Resposta: " . $response_text];
            }
        } else {
            return ['success' => false, 'error' => "Erro ao enviar mídia. Código HTTP: " . $http_code_media . ", Resposta: " . $response_media];
        }
    } else {
        // Envia a mensagem de texto (sem arquivo)
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $data = json_encode(['number' => $numero, 'body' => $mensagem], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($http_code == 200) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => "Código HTTP: " . $http_code . ", Resposta: " . $response];
        }
    }
}

// Obtém os dados do POST
$mensagem = $_POST['mensagem'];
$arquivo_path = $_FILES['arquivo']['tmp_name'] ? $_FILES['arquivo']['tmp_name'] : '';

// Consulta para obter os leads do usuário
$stmt = $pdo->prepare("SELECT id, nome, numero FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializa as variáveis de controle
$total_enviados = 0;
$erros_envio = [];
$token = $usuario['token_dispositivo'];

// Loop pelos leads e envia as mensagens
foreach ($leads as $lead) {
    $numero = $lead['numero'];
    $nome = $lead['nome'];
    $mensagem_personalizada = str_replace('{nome}', $nome, $mensagem);

    // Envio da mensagem
    $resultado = send_message($token, $numero, $mensagem_personalizada, $arquivo_path);

    if ($resultado['success']) {
        $total_enviados++;
    } else {
        $erros_envio[] = "Erro ao enviar mensagem para " . htmlspecialchars($numero) . ": " . htmlspecialchars($resultado['error']);
    }

    // Espaço de tempo aleatório entre 5 e 15 segundos
    sleep(rand(5, 15));
}

// Atualiza as variáveis de sessão
$_SESSION['envio_em_andamento'] = false;
$_SESSION['total_enviados'] = $total_enviados;
$_SESSION['erros_envio'] = $erros_envio;

// Envia uma resposta para o AJAX
echo "Envio concluído. Total de mensagens enviadas: " . htmlspecialchars($total_enviados);
?>