<?php
session_start();
include '../includes/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo "Acesso negado.";
    exit;
}

// Garante que a execução não exceda um tempo limite razoável
set_time_limit(300); // 5 minutos

<<<<<<< HEAD
// Função atualizada para enviar a mensagem
function send_message($token, $numero, $mensagem, $arquivo = null) {
    $url = 'http://localhost:3000/send-message';
    
    $data = [
        'number' => $numero,
        'message' => $mensagem
    ];

    // Processa o arquivo se existir
    if ($arquivo && $arquivo['error'] == UPLOAD_ERR_OK) {
        $arquivo_temp = $arquivo['tmp_name'];
        $arquivo_nome = basename($arquivo['name']);
        $upload_path = '../uploads/' . $arquivo_nome;
        
        // Move o arquivo para o diretório de uploads
        if (move_uploaded_file($arquivo_temp, $upload_path)) {
            $data['mediaPath'] = $upload_path;
=======
// Função para enviar a mensagem
function send_message($token, $numero, $mensagem, $arquivo = null) {
    $url = 'https://api2.publicidadeja.com.br/api/messages/send';
    
    // Verifica se há arquivo para enviar
    if ($arquivo && $arquivo['error'] == UPLOAD_ERR_OK) {
        // Processa o upload do arquivo
        $arquivo_temp = $arquivo['tmp_name'];
        $arquivo_nome = preg_replace('/[^a-zA-Z0-9\.]/', '', basename($arquivo['name']));
        $arquivo_tipo = mime_content_type($arquivo_temp);
        
        // Cria o CURLFile para o arquivo
        $cfile = new CURLFile($arquivo_temp, $arquivo_tipo, $arquivo_nome);
        
        // Prepara os dados para envio da mídia
        $post_data_media = [
            'number' => $numero,
            'medias' => $cfile
        ];

        $headers_media = [
            'Authorization: Bearer ' . $token,
            'Content-Type: multipart/form-data'
        ];

        // Envia a mídia
        $ch_media = curl_init();
        curl_setopt($ch_media, CURLOPT_URL, $url);
        curl_setopt($ch_media, CURLOPT_POST, true);
        curl_setopt($ch_media, CURLOPT_HTTPHEADER, $headers_media);
        curl_setopt($ch_media, CURLOPT_POSTFIELDS, $post_data_media);
        curl_setopt($ch_media, CURLOPT_RETURNTRANSFER, true);

        $response_media = curl_exec($ch_media);
        $http_code_media = curl_getinfo($ch_media, CURLINFO_HTTP_CODE);
        curl_close($ch_media);

        // Se a mídia foi enviada com sucesso, envia o texto
        if ($http_code_media == 200) {
            // Pequena pausa para garantir que a mídia foi processada
            sleep(2);
            
            // Envia o texto
            $headers_text = [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ];

            $data_text = json_encode([
                'number' => $numero,
                'body' => $mensagem
            ], JSON_UNESCAPED_UNICODE);

            $ch_text = curl_init();
            curl_setopt($ch_text, CURLOPT_URL, $url);
            curl_setopt($ch_text, CURLOPT_POST, true);
            curl_setopt($ch_text, CURLOPT_HTTPHEADER, $headers_text);
            curl_setopt($ch_text, CURLOPT_POSTFIELDS, $data_text);
            curl_setopt($ch_text, CURLOPT_RETURNTRANSFER, true);

            $response_text = curl_exec($ch_text);
            $http_code_text = curl_getinfo($ch_text, CURLINFO_HTTP_CODE);
            curl_close($ch_text);

            if ($http_code_text == 200) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => "Erro ao enviar texto. HTTP: " . $http_code_text];
            }
        } else {
            return ['success' => false, 'error' => "Erro ao enviar mídia. HTTP: " . $http_code_media];
>>>>>>> 249418c522ecfc780fbd512ea7b1495ce7a67ed0
        }
    }

    // Configura e executa o CURL
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        return ['success' => true];
    } else {
<<<<<<< HEAD
        return ['success' => false, 'error' => "Erro ao enviar mensagem. HTTP: " . $http_code];
=======
        // Envia apenas o texto (sem arquivo)
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $data = json_encode([
            'number' => $numero,
            'body' => $mensagem
        ], JSON_UNESCAPED_UNICODE);

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
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => "Erro ao enviar mensagem. HTTP: " . $http_code];
        }
>>>>>>> 249418c522ecfc780fbd512ea7b1495ce7a67ed0
    }
}

// Obtém os dados do POST
$mensagem = $_POST['mensagem'];
$arquivo = isset($_FILES['arquivo']) ? $_FILES['arquivo'] : null;

// Consulta para obter os leads do usuário
$stmt = $pdo->prepare("SELECT id, nome, numero FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obter o token do usuário
$stmt_usuario = $pdo->prepare("SELECT token_dispositivo FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$_SESSION['usuario_id']]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
$token = $usuario['token_dispositivo'];

// Inicializa as variáveis de controle
$total_enviados = 0;
$erros_envio = [];
<<<<<<< HEAD

// Verifica e cria o diretório de uploads se não existir
$upload_dir = '../uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
=======
>>>>>>> 249418c522ecfc780fbd512ea7b1495ce7a67ed0

// Loop pelos leads e envia as mensagens
foreach ($leads as $lead) {
    $numero = $lead['numero'];
    $nome = $lead['nome'];
    $mensagem_personalizada = str_replace('{nome}', $nome, $mensagem);

<<<<<<< HEAD
    // Envio da mensagem com arquivo (se existir)
=======
    // Envio da mensagem
>>>>>>> 249418c522ecfc780fbd512ea7b1495ce7a67ed0
    $resultado = send_message($token, $numero, $mensagem_personalizada, $arquivo);

    if ($resultado['success']) {
        $total_enviados++;
    } else {
        $erros_envio[] = "Erro ao enviar para {$numero}: {$resultado['error']}";
    }

    // Intervalo entre envios (5 a 15 segundos)
    sleep(rand(5, 15));
}

// Atualiza as variáveis de sessão
$_SESSION['envio_em_andamento'] = false;
$_SESSION['total_enviados'] = $total_enviados;
$_SESSION['erros_envio'] = $erros_envio;

// Retorna resposta
echo json_encode([
    'status' => 'success',
    'message' => "Envio concluído. Total de mensagens enviadas: {$total_enviados}",
    'total_enviados' => $total_enviados,
    'erros' => $erros_envio
]);
<<<<<<< HEAD
?>
=======
?>
>>>>>>> 249418c522ecfc780fbd512ea7b1495ce7a67ed0
