<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';

// Funções auxiliares (já existentes)
function formatarNumeroWhatsApp($numero) {
    $numero = preg_replace('/[^0-9]/', '', $numero);
    if (!str_starts_with($numero, '55')) {
        $numero = '55' . $numero;
    }
    return $numero;
}

function validarNumero($numero) {
    $numero_limpo = preg_replace('/[^0-9]/', '', $numero);
    return strlen($numero_limpo) >= 11 && strlen($numero_limpo) <= 13;
}

// Consultas iniciais (já existentes)
$stmt = $pdo->prepare("SELECT d.*, u.mensagem_base FROM dispositivos d 
                       JOIN usuarios u ON u.id = d.usuario_id 
                       WHERE d.usuario_id = ? AND d.status = 'CONNECTED' 
                       ORDER BY d.created_at DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuração da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$leads_por_pagina = 10;
$offset = ($pagina - 1) * $leads_por_pagina;

// Consulta para obter os leads com paginação
$stmt = $pdo->prepare("SELECT id, nome, numero, data_envio, status FROM leads_enviados 
                       WHERE usuario_id = ? 
                       ORDER BY data_envio DESC 
                       LIMIT ? OFFSET ?");
$stmt->bindValue(1, $_SESSION['usuario_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $leads_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obter o total de leads (para a paginação)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$total_leads = $stmt->fetchColumn();
$total_paginas = ceil($total_leads / $leads_por_pagina);

// Buscar mensagem base do usuário (já existente)
$stmt = $pdo->prepare("SELECT mensagem_base FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$mensagem_base = $usuario['mensagem_base'] ?? '';

// Variáveis de controle (já existentes)
$mensagem_enviada = false;
$erros_envio = [];
$total_enviados = 0;
$arquivo_path = ''; // Inicializa o caminho do arquivo

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dispositivo_id = $_POST['dispositivo_id'];
    $mensagem = $_POST['mensagem'];
    $arquivo_path = '';

<<<<<<< HEAD
    // Processar upload do arquivo
=======
    if (empty($_POST['mensagem'])) {
        $erros_envio[] = "O campo mensagem não pode estar vazio.";
    }

    // Processar upload do arquivo
    $arquivo_path = '';
>>>>>>> 249418c522ecfc780fbd512ea7b1495ce7a67ed0
    if ($_FILES['arquivo']['error'] == UPLOAD_ERR_OK) {
        $nome_temporario = $_FILES['arquivo']['tmp_name'];
        $nome_arquivo = $_FILES['arquivo']['name'];
        $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

        if (in_array(strtolower($extensao), $extensoes_permitidas)) {
            $diretorio_destino = '../uploads/';
            $nome_final = uniqid() . '.' . $extensao;
            $arquivo_path = $diretorio_destino . $nome_final;

            if (move_uploaded_file($nome_temporario, $arquivo_path)) {
                // Arquivo movido com sucesso
            } else {
                $erros_envio[] = "Erro ao mover o arquivo para o servidor.";
                $arquivo_path = '';
            }
        }
    }

<<<<<<< HEAD
    // Preparar dados para envio em massa
    foreach ($leads as $lead) {
        $numero = formatarNumeroWhatsApp($lead['numero']);
        $mensagem_personalizada = str_replace('{nome}', $lead['nome'], $mensagem);
=======
    // Preparar dados para envio
    $data = [
        'deviceId' => $dispositivo_id,
        'number' => $numero,
        'message' => $mensagem_personalizada
    ];

    // Adicionar arquivo se existir
    if (!empty($arquivo_path) && file_exists($arquivo_path)) {
        $data['mediaPath'] = $arquivo_path;
    }

    // Enviar mensagem via API
    $ch = curl_init('http://localhost:3000/send-message');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    // Verificar status do dispositivo (já existente)
    if (!empty($_POST['dispositivo_id'])) {
        $stmt = $pdo->prepare("SELECT status FROM dispositivos WHERE device_id = ? AND usuario_id = ?");
        $stmt->execute([$_POST['dispositivo_id'], $_SESSION['usuario_id']]);
        $device = $stmt->fetch();
>>>>>>> 249418c522ecfc780fbd512ea7b1495ce7a67ed0

        $data = [
            'deviceId' => $dispositivo_id,
            'number' => $numero,
            'message' => $mensagem_personalizada
        ];

        // Adicionar arquivo se existir
        if (!empty($arquivo_path) && file_exists($arquivo_path)) {
            $data['mediaPath'] = $arquivo_path;
        }

        // Enviar mensagem via API
        $ch = curl_init('http://localhost:3000/send-message');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $erros_envio[] = "Erro ao enviar para {$lead['numero']}: " . curl_error($ch);
            continue;
        }

        $result = json_decode($response, true);

        if ($http_code == 200 && isset($result['success']) && $result['success']) {
            // Atualizar status do lead
            $stmt = $pdo->prepare("UPDATE leads_enviados SET 
                status = 'ENVIADO',
                data_envio = NOW(),
                mensagem = ?,
                arquivo = ?
                WHERE id = ?");
            $stmt->execute([
                $mensagem_personalizada,
                basename($arquivo_path),
                $lead['id']
            ]);
            
            $total_enviados++;
        } else {
            $error_message = isset($result['message']) ? $result['message'] : 'Erro desconhecido';
            $erros_envio[] = "Erro ao enviar para {$lead['numero']}: {$error_message}";
        }

        curl_close($ch);
        
        // Intervalo entre envios para evitar bloqueio
        sleep(rand(2, 5));
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio em Massa - ZapLocal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS (Material Design) -->
    <style>
        /* Cores ZapLocal */
        :root {
            --primary-color: #3547DB;
            --primary-hover: #283593;
            --success-color: #2CC149;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --background-color: #f7f9fc;
            --text-color: #364a63;
            --border-color: #e2e8f0;
            --card-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
            --border-radius: 10px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        /* Header */
        .navbar {
            background-color: #fff;
            box-shadow: var(--card-shadow);
            padding: 1rem 1.5rem;
        }

        .navbar-brand img {
            height: 40px;
        }

        .navbar-toggler {
            border: none;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        /* Header Icons */
        .navbar-icons {
            display: flex;
            align-items: center;
        }

        .navbar-icons a {
            color: var(--text-color);
            margin-left: 1rem;
            font-size: 1.2rem;
            transition: color 0.2s ease;
        }

        .navbar-icons a:hover {
            color: var(--primary-color);
        }

        /* Container */
        .container {
            padding-top: 20px;
        }

        /* Sidebar */
        .sidebar {
            background-color: #fff;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 0.85rem;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: #4e5d78;
            text-decoration: none;
            padding: 0.85rem 1.15rem;
            border-radius: 8px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar a:hover {
            background-color: #e2e8f0;
            color: #2e384d;
        }

        .sidebar i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        /* Form Container */
        .form-container {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-title {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        /* Form Controls */
        .form-label {
            color: var(--text-color);
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border-color: var(--border-color);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb), .25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        /* Status Badge */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Alertas */
        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        /* AI Assistant */
        #aiResponse {
            min-height: 100px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .ai-thinking {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        /* Paginação */
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            color: var(--primary-color);
            border-color: var(--border-color);
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        /* Notificações */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            z-index: 1050;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .notification.show {
            opacity: 1;
        }

        .notification.success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .notification.error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .form-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/Logo-ZapLocal-fundo-escuro-1.png" alt="ZapLocal Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="navbar-icons">
                        <a href="#"><i class="fas fa-bell"></i></a>
                        <a href="#"><i class="fas fa-user-circle"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Conteúdo Principal -->
            <div class="col-md-9">
                <div class="form-container">
                    <h2 class="form-title"><i class="fas fa-paper-plane me-2"></i>Envio em Massa</h2>

                    <?php if (!empty($erros_envio)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($erros_envio as $erro): ?>
                                    <li><?php echo $erro; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="massMessageForm" method="POST" enctype="multipart/form-data">
                        <!-- Seleção de Dispositivo -->
                        <div class="mb-3">
                            <label for="dispositivo" class="form-label">Selecione o Dispositivo</label>
                            <select class="form-select" name="dispositivo_id" id="dispositivo" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($dispositivos as $dispositivo): ?>
                                    <option value="<?php echo htmlspecialchars($dispositivo['device_id']); ?>">
                                        <?php echo htmlspecialchars($dispositivo['nome']); ?> 
                                        (<?php echo htmlspecialchars($dispositivo['numero']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Seção de Mensagem com IA Assistant -->
                        <div class="mb-4">
                            <label for="mensagem" class="form-label">Mensagem</label>
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm me-2" id="btnSugestao">
                                    <i class="fas fa-magic"></i> Sugerir Melhorias
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCriarMensagem">
                                    <i class="fas fa-pen"></i> Criar Nova
                                </button>
                            </div>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="4" required><?php echo htmlspecialchars($mensagem_base); ?></textarea>
                            <div class="form-text">Use {nome} para incluir o nome do lead na mensagem.</div>
                        </div>

                        <!-- AI Assistant -->
                        <div id="aiAssistant" class="mb-3 d-none">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <span class="text-primary"><i class="fas fa-robot me-2"></i>Assistente IA</span>
                                    <button type="button" id="btnFecharAssistente" class="btn btn-sm btn-close"></button>
                                </div>
                                <div class="card-body">
                                    <div class="ai-thinking d-none">
                                        <div class="d-flex align-items-center">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                            <span>Processando sua solicitação...</span>
                                        </div>
                                    </div>
                                    <div id="aiResponse"></div>
                                    <div class="mt-3 text-end d-none" id="aiActions">
                                        <button type="button" class="btn btn-success btn-sm" id="btnUsarSugestao">
                                            <i class="fas fa-check me-1"></i>Adicionar Sugestão
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload de Arquivo -->
                        <div class="mb-4">
                            <label for="arquivo" class="form-label">Arquivo (opcional)</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="arquivo" name="arquivo">
                                <button type="button" class="btn btn-outline-secondary" id="btnLimparArquivo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="form-text">Formatos permitidos: jpg, jpeg, png, pdf, doc, docx</div>
                            <input type="hidden" id="caminhoArquivo" name="caminhoArquivo" value="<?php echo htmlspecialchars($arquivo_path); ?>">
                        </div>

                        <!-- Botão de Envio -->
                        <button type="submit" class="btn btn-primary" id="btnEnviar">
                            <i class="fas fa-paper-plane me-2"></i>Iniciar Envio
                        </button>
                    </form>

                    <!-- Lista de Leads com Paginação -->
                    <h4 class="mt-5">Lista de Leads</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Número</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leads as $lead): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lead['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['numero']); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = isset($lead['status']) && $lead['status'] == 'ENVIADO' 
                                                    ? 'success' 
                                                    : 'warning';
                                                $statusText = isset($lead['status']) 
                                                    ? htmlspecialchars($lead['status']) 
                                                    : 'PENDENTE';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($total_paginas > 1): ?>
                                <li class="page-item <?php echo ($pagina == 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">«</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($pagina == $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>" aria-label="Próximo">
                                        <span aria-hidden="true">»</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Claude AI Integration - Frontend Code
        const PROXY_URL = 'claude_proxy.php';

        async function generateWithClaude(prompt) {
            try {
                console.log('Enviando prompt:', prompt);

                const response = await fetch(PROXY_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        prompt: prompt
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Resposta completa da API:', data);

                if (!data.success) {
                    throw new Error(data.error || 'Erro desconhecido na API');
                }

                if (!data.content || typeof data.content !== 'string') {
                    throw new Error('Resposta sem conteúdo válido');
                }

                return data.content;

            } catch (error) {
                console.error('Erro detalhado:', error);
                throw error;
            }
        }

        // AI Assistant Controls
        $(document).ready(function() {
            const $aiAssistant = $('#aiAssistant');
            const $aiThinking = $('.ai-thinking');
            const $aiResponse = $('#aiResponse');
            const $mensagem = $('#mensagem');
            const $aiActions = $('#aiActions');
            const $btnUsarSugestao = $('#btnUsarSugestao');

            function showError(message) {
                const errorMessage = typeof message === 'object' ? 
                    JSON.stringify(message, null, 2) : message;
                    
                $aiResponse.html(`
                    <div class="alert alert-danger">
                        <strong>Erro:</strong> ${errorMessage}<br>
                        <small>Por favor, tente novamente. Se o erro persistir, contate o suporte.</small>
                    </div>
                `);
                $aiActions.addClass('d-none');
            }

            function showSuccess(content, title = 'Sugestão') {
                if (!content) {
                    showError('Conteúdo da resposta vazio');
                    return;
                }

                const sanitizedContent = content
                    .replace(/</g, '<')
                    .replace(/>/g, '>')
                    .replace(/\n/g, '<br>');

                $aiResponse.html(`
                    <div class="alert alert-success">
                        <strong>${title}:</strong><br>
                        ${sanitizedContent}
                    </div>
                `);
                $aiActions.removeClass('d-none');
            }

            async function processAIRequest(prompt, type = 'sugestão') {
                try {
                    if (!prompt) {
                        throw new Error('Prompt não pode estar vazio');
                    }

                    $aiAssistant.removeClass('d-none');
                    $aiThinking.removeClass('d-none');
                    $aiResponse.empty();
                    $aiActions.addClass('d-none');

                    console.log('Processando requisição:', type);
                    const result = await generateWithClaude(prompt);
                    
                    if (result) {
                        showSuccess(result, type === 'sugestão' ? 'Sugestão' : 'Mensagem Gerada');
                    } else {
                        throw new Error(`Não foi possível gerar a ${type}`);
                    }

                } catch (error) {
                    console.error('Erro ao processar requisição:', error);
                    showError(error.message || 'Erro desconhecido ao processar requisição');
                } finally {
                    $aiThinking.addClass('d-none');
                }
            }

            $('#btnSugestao').click(async function() {
                const currentText = $mensagem.val().trim();
                if (!currentText) {
                    showError('Por favor, insira uma mensagem para receber sugestões.');
                    return;
                }

                const prompt = `
                    Analise e melhore esta mensagem de WhatsApp:
                    "${currentText}"
                    
                    Requisitos:
                    - Mantenha o tom profissional e amigável
                    - Torne a mensagem mais persuasiva
                    - Mantenha a essência do conteúdo original
                    - Adicione elementos de engajamento
                    - Use emojis apropriados
                    - Mantenha a mensagem concisa
                    
                    Responda apenas com a mensagem melhorada, sem explicações adicionais.
                `.trim();

                await processAIRequest(prompt, 'sugestão');
            });

            $('#btnCriarMensagem').click(async function() {
                const userPrompt = prompt('Sobre qual assunto você quer criar a mensagem?');
                if (!userPrompt || !userPrompt.trim()) return;

                const prompt = `
                    Crie uma mensagem persuasiva de WhatsApp sobre: "${userPrompt.trim()}"
                    
                    Requisitos:
                    - Tom profissional e amigável
                    - Inclua call-to-action claro
                    - Use emojis apropriados
                    - Máximo de 200 caracteres
                    - Estrutura: Saudação → Contexto → Benefício → Call-to-action
                    - Linguagem natural e envolvente
                    
                    Responda apenas com a mensagem, sem explicações adicionais.
                `.trim();

                await processAIRequest(prompt, 'mensagem');
            });

            $btnUsarSugestao.click(function() {
                const $successAlert = $aiResponse.find('.alert-success');
                if (!$successAlert.length) {
                    showError('Nenhuma sugestão disponível para usar');
                    return;
                }

                const suggestion = $successAlert.text()
                    .replace('Sugestão:', '')
                    .replace('Mensagem Gerada:', '')
                    .trim();
                    
                if (suggestion) {
                    $mensagem.val(suggestion);
                    updateMessagePreview();
                    $aiAssistant.addClass('d-none');
                } else {
                    showError('Nenhum conteúdo disponível na sugestão');
                }
            });

            let previewTimeout;
            $mensagem.on('input', function() {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(updateMessagePreview, 500);
            });

            function updateMessagePreview() {
                const messageText = $mensagem.val();
                if (!messageText) {
                    $('#messagePreview').html('Preview da mensagem...');
                    return;
                }

                const sanitizedText = messageText
                    .replace(/</g, '<')
                    .replace(/>/g, '>')
                    .replace(/\n/g, '<br>');

                $('#messagePreview').html(sanitizedText);
            }

            // Limpa timeouts pendentes ao desmontar
            $(window).on('unload', function() {
                if (previewTimeout) {
                    clearTimeout(previewTimeout);
                }
            });

            // Inicializa o preview
            updateMessagePreview();

            // Adiciona botão para fechar o assistente
            $('#btnFecharAssistente').click(function() {
                $aiAssistant.addClass('d-none');
            });

            // Tratamento de erros global
            window.onerror = function(msg, url, line, col, error) {
                console.error('Erro global:', {msg, url, line, col, error});
                showError('Erro inesperado. Por favor, tente novamente.');
                $aiThinking.addClass('d-none');
                return false;
            };
        });
    </script>
<script>
        $(document).ready(function() {
            const leads = <?php echo json_encode($leads); ?>;
            let currentLeadIndex = 0;

            // Atualiza preview da mensagem
            $('#mensagem').on('input', updateMessagePreview);

            function updateMessagePreview() {
                let mensagem = $('#mensagem').val();
                if (leads.length > 0) {
                    mensagem = mensagem.replace('{nome}', leads[0].nome);
                }
                $('#messagePreview').html(mensagem.replace(/\n/g, '<br>'));
            }

            // Inicializa preview
            updateMessagePreview();

            $('#massMessageForm').on('submit', function(e) {
                e.preventDefault(); // Impede o envio padrão do formulário

                const confirmacao = confirm(`Você está prestes a enviar mensagens para ${leads.length} leads. Deseja continuar?`);
                if (confirmacao) {
                    iniciarEnvioEmMassa();
                }
            });

            function finalizarEnvio() {
                $('#btnEnviar').prop('disabled', false);
                mostrarNotificacao('Envio em massa concluído!\nTotal de mensagens enviadas: ' + processedLeads.size, 'success');

                // Atualiza a página para mostrar o status atualizado dos leads
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }

            let processedLeads = new Set(); // Conjunto para rastrear leads já processados

            function enviarProximaMensagem() {
                if (currentLeadIndex >= leads.length) {
                    finalizarEnvio();
                    return;
                }

                const lead = leads[currentLeadIndex];

                // Verifica se o lead já foi processado
                if (processedLeads.has(lead.id)) {
                    currentLeadIndex++;
                    enviarProximaMensagem();
                    return;
                }

                $('#currentLead').text(currentLeadIndex + 1);
                const progress = ((currentLeadIndex + 1) / leads.length) * 100;
                $('.progress-bar').css('width', progress + '%');

                const deviceId = $('#dispositivo').val();

                if (!deviceId) {
                    mostrarNotificacao('Erro: Dispositivo não selecionado', 'error');
                    return;
                }

                // Formatar o número corretamente
                let numero = lead.numero.replace(/\D/g, '');
                if (numero.length === 10 || numero.length === 11) {
                    if (!numero.startsWith('55')) {
                        numero = '55' + numero;
                    }
                }

                // Obter o caminho do arquivo do campo oculto
                const filePath = $('#caminhoArquivo').val();

                const data = {
                    deviceId: deviceId,
                    number: numero,
                    message: $('#mensagem').val().replace('{nome}', lead.nome),
                    mediaPath: filePath // Adiciona o caminho do arquivo
                };

                $.ajax({
                    url: 'http://localhost:3000/send-message',
                    type: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.success) {
                            processedLeads.add(lead.id); // Marca o lead como processado

                            // Registra o envio no banco
                            $.post('registrar_envio.php', {
                                lead_id: lead.id,
                                dispositivo_id: deviceId,
                                status: 'ENVIADO',
                                arquivo: filePath // Salva o caminho do arquivo no banco
                            });

                            mostrarNotificacao('Mensagem enviada com sucesso para ' + lead.nome, 'success');
                        } else {
                            mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + response.message, 'error');
                        }

                        currentLeadIndex++;
                        setTimeout(enviarProximaMensagem, Math.random() * 5000 + 5000);
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro na requisição:', xhr.responseText);
                        mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + error, 'error');

currentLeadIndex++;
setTimeout(enviarProximaMensagem, 5000);
}
});
}

function iniciarEnvioEmMassa() {
processedLeads.clear(); // Limpa o conjunto de leads processados
currentLeadIndex = 0;

// Verifica se há leads para enviar
if (leads.length === 0) {
mostrarNotificacao('Não há leads para enviar mensagens.', 'error');
return;
}

// Verifica o dispositivo selecionado
const deviceId = $('#dispositivo').val();
if (!deviceId) {
mostrarNotificacao('Por favor, selecione um dispositivo.', 'error');
return;
}

$('#btnEnviar').prop('disabled', true);
$('#progressBar, #sendingStatus').removeClass('d-none');
$('#totalLeads').text(leads.length);

enviarProximaMensagem();
}

function validarNumeroTelefone($numero) {
$numero = preg_replace('/[^0-9]/', '', $numero);
return strlen($numero) === 10 || strlen($numero) === 11;
}

function validarNumeroWhatsApp($numero) {
$numero = preg_replace('/[^0-9]/', '', $numero);
if (!str_starts_with($numero, '55')) {
$numero = '55' + $numero;
}
return strlen($numero) >= 12 && strlen($numero) <= 13 ? $numero : false;
}

// Função para mostrar notificações
function mostrarNotificacao(mensagem, tipo) {
const $notificacao = $('<div class="notification ' + tipo + '">' + mensagem + '</div>');
$('body').append($notificacao);
$notificacao.addClass('show');

// Remove a notificação após 3 segundos
setTimeout(function() {
$notificacao.removeClass('show');
setTimeout(function() {
$notificacao.remove();
}, 300); // Aguarda a transição terminar
}, 3000);
}
});
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>
</html>
