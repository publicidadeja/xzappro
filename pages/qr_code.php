<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirecionarSeNaoLogado();

// Verificar se device_id foi fornecido
if (!isset($_GET['device_id'])) {
    header('Location: dispositivos.php');
    exit;
}

$device_id = $_GET['device_id'];

// Verificar se o dispositivo pertence ao usuário
$stmt = $pdo->prepare("SELECT * FROM dispositivos WHERE device_id = ? AND usuario_id = ?");
$stmt->execute([$device_id, $_SESSION['usuario_id']]);
$dispositivo = $stmt->fetch();

if (!$dispositivo) {
    header('Location: dispositivos.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - ZapLocal</title>
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
            --primary-color: #3547DB; /* Azul institucional */
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
            height: 40px; /* Ajuste a altura do logo */
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

        /* QR Container */
        .qr-container {
            text-align: center;
            padding: 20px;
            margin: 20px auto;
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        #qrcode img {
            margin: 20px auto;
            max-width: 300px;
            height: auto;
        }

        .loading {
            margin: 20px auto;
            text-align: center;
        }

        /* Steps */
        .steps {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }

        .step {
            text-align: center;
            position: relative;
            flex: 1;
            padding: 20px;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: var(--success-color);
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .step.active .step-number {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Loading Animation */
        .loading-animation {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .loading-animation div {
            position: absolute;
            border: 4px solid var(--success-color);
            opacity: 1;
            border-radius: 50%;
            animation: loading-animation 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }

        @keyframes loading-animation {
            0% {
                top: 36px;
                left: 36px;
                width: 0;
                height: 0;
                opacity: 1;
            }
            100% {
                top: 0px;
                left: 0px;
                width: 72px;
                height: 72px;
                opacity: 0;
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
        <div class="qr-container">
            <h3>Conectar WhatsApp</h3>
            
            <div class="steps">
                <div class="step active" id="step1">
                    <div class="step-number">1</div>
                    <p>Gerando QR Code</p>
                </div>
                <div class="step" id="step2">
                    <div class="step-number">2</div>
                    <p>Abra o WhatsApp no seu celular</p>
                </div>
                <div class="step" id="step3">
                    <div class="step-number">3</div>
                    <p>Escaneie o código</p>
                </div>
            </div>

            <div id="qrcode">
                <div class="loading">
                    <div class="loading-animation">
                        <div></div>
                    </div>
                    <p>Gerando QR Code...</p>
                </div>
            </div>
            <div id="status" class="mt-3"></div>
            <div class="mt-3">
                <a href="dispositivos.php" class="btn btn-secondary">Voltar</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const deviceId = '<?php echo htmlspecialchars($device_id); ?>';
        let checkQrInterval;
        
        function checkQRCode() {
            $.get(`http://localhost:3000/get-qr/${deviceId}`)
                .then(response => {
                    if (response.success) {
                        // Se tiver QR code, exibe ele
                        if (response.qr) {
                            updateSteps(2);
                            $('#qrcode').html(`<img src="https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(response.qr)}&size=300x300">`);
                            $('#status').html('<div class="alert alert-info">Aguardando leitura do QR Code...</div>');
                        } 
                        // Só redireciona se realmente estiver conectado
                        else if (response.status === 'CONNECTED') {
                            updateSteps(3);
                            clearInterval(checkQrInterval);
                            $('#qrcode').html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> Dispositivo conectado!</div>');
                            $('#status').html('<div class="alert alert-success">Redirecionando...</div>');
                            setTimeout(() => {
                                window.location.href = 'dispositivos.php';
                            }, 3000);
                        }
                        // Se não tiver QR code e não estiver conectado, mostra loading
                        else {
                            updateSteps(1);
                            $('#qrcode').html('<div class="loading"><div class="loading-animation"><div></div></div><p>Aguardando QR Code...</p></div>');
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar QR code:', error);
                    $('#status').html('<div class="alert alert-danger">Erro ao verificar status</div>');
                });
        }

        // Função para iniciar o dispositivo
        function initDevice() {
            $('#qrcode').html('<div class="loading"><div class="loading-animation"><div></div></div><p>Iniciando dispositivo...</p></div>');
            
            $.ajax({
                url: 'http://localhost:3000/init-device',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ deviceId: deviceId }),
                success: function(response) {
                    if (response.success) {
                        console.log('Dispositivo iniciado, aguardando QR code...');
                        startQRCodeCheck();
                    } else {
                        $('#status').html('<div class="alert alert-danger">Erro ao iniciar dispositivo: ' + response.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao iniciar dispositivo:', error);
                    $('#status').html('<div class="alert alert-danger">Erro ao iniciar dispositivo</div>');
                }
            });
        }

        // Função para iniciar a verificação do QR code
        function startQRCodeCheck() {
            // Verificar imediatamente
            checkQRCode();
            
            // Configurar intervalo para verificar a cada 2 segundos
            checkQrInterval = setInterval(checkQRCode, 2000);
        }

        // Iniciar o processo quando a página carregar
        $(document).ready(function() {
            initDevice();
        });

        // Limpar intervalo quando a página for fechada
        $(window).on('beforeunload', function() {
            if (checkQrInterval) {
                clearInterval(checkQrInterval);
            }
        });

        function updateSteps(currentStep) {
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.querySelector(`#step${currentStep}`).classList.add('active');
        }
    </script>
</body>
</html>