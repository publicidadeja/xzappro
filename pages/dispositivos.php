<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';

// Excluir Dispositivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $device_id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM dispositivos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$device_id, $_SESSION['usuario_id']]);
    $_SESSION['mensagem'] = "Dispositivo excluído com sucesso!";
}

// Consulta para obter os dispositivos do usuário
$stmt = $pdo->prepare("SELECT * FROM dispositivos WHERE usuario_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Dispositivos - ZapLocal</title>
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
            margin: 0; /* Reset margin */
            padding: 0; /* Reset padding */
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

        /* Container principal */
        .container {
            padding-top: 20px;
        }

        /* Tabela de dispositivos */
        .table-container {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-top: 2rem;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid var(--border-color);
            color: var(--text-color);
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        /* Status badge */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-connected { background: #d4edda; color: #155724; }
        .status-disconnected { background: #f8d7da; color: #721c24; }
        .status-waiting_qr { background: #fff3cd; color: #856404; }

        /* Botão adicionar dispositivo */
        .btn-add-device {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-add-device:hover {
            background-color: #239f13;
            transform: translateY(-2px);
        }

        /* Alertas */
        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        /* Modais */
        .modal-content {
            border-radius: var(--border-radius);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                margin-bottom: 1rem;
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
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo htmlspecialchars($_SESSION['mensagem']);
                        unset($_SESSION['mensagem']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Dispositivos Conectados</h2>
                        <button class="btn-add-device" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                            <i class="fas fa-plus me-2"></i> Novo Dispositivo
                        </button>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Status</th>
                                <th>Data de Criação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dispositivos as $dispositivo): ?>
                            <tr data-device-id="<?php echo htmlspecialchars($dispositivo['device_id']); ?>">
                                <td><?php echo htmlspecialchars($dispositivo['nome']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($dispositivo['status']) {
                                        case 'CONNECTED':
                                            $statusClass = 'success';
                                            $statusText = 'Conectado';
                                            break;
                                        case 'WAITING_QR':
                                            $statusClass = 'warning';
                                            $statusText = 'Aguardando QR';
                                            break;
                                        default:
                                            $statusClass = 'danger';
                                            $statusText = 'Desconectado';
                                    }
                                    ?>
                                    <span class="status-badge status-<?php echo htmlspecialchars(strtolower($statusText)); ?>">
                                        <?php echo htmlspecialchars($statusText); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($dispositivo['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="reconnectDevice('<?php echo htmlspecialchars($dispositivo['device_id']); ?>')">
                                        <i class="fas fa-sync-alt"></i> Reconectar
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($dispositivo['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este dispositivo?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add Device -->
    <div class="modal fade" id="addDeviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo Dispositivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addDeviceForm">
                        <div class="mb-3">
                            <label class="form-label">Nome do Dispositivo</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="addDevice()">Adicionar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addDevice() {
            const form = document.getElementById('addDeviceForm');
            const formData = new FormData(form);

            if (!formData.get('nome')) {
                alert('Nome do dispositivo é obrigatório');
                return;
            }

            fetch('add_device.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = `qr_code.php?device_id=${data.device_id}`;
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao adicionar dispositivo: ' + error.message);
            });
        }

        function reconnectDevice(deviceId) {
            // Mostrar loading
            const statusBadge = document.querySelector(`[data-device-id="${deviceId}"] .status-badge`);
            if (statusBadge) {
                statusBadge.textContent = 'Reconectando...';
            }

            fetch('http://localhost:3000/init-device', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ deviceId: deviceId })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = `qr_code.php?device_id=${deviceId}`;
                } else {
                    throw new Error(data.message || 'Erro desconhecido ao reconectar');
                }
            })
            .catch(error => {
                console.error('Erro detalhado:', error);
                alert('Erro ao reconectar dispositivo. Verifique se o servidor está rodando e tente novamente.');
                
                // Restaurar status para desconectado em caso de erro
                if (statusBadge) {
                    statusBadge.textContent = 'Desconectado';
                }
            });
        }
        

        // Função para atualizar status dos dispositivos
        function updateDeviceStatuses() {
            const devices = document.querySelectorAll('[data-device-id]');
            devices.forEach(device => {
                const deviceId = device.dataset.deviceId;
                fetch(`http://localhost:3000/check-status/${deviceId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const statusBadge = device.querySelector('.status-badge');
                            if (statusBadge) {
                                let statusClass = '';
                                let statusText = '';
                                switch (data.status) {
                                    case 'CONNECTED':
                                        statusClass = 'success';
                                        statusText = 'Conectado';
                                        break;
                                    case 'WAITING_QR':
                                        statusClass = 'warning';
                                        statusText = 'Aguardando QR';
                                        break;
                                    default:
                                        statusClass = 'danger';
                                        statusText = 'Desconectado';
                                }
                                statusBadge.className = `status-badge status-${statusText.toLowerCase()}`;
                                statusBadge.textContent = statusText;
                            }
                        }
                    })
                    .catch(console.error);
            });
        }

        // Atualizar status a cada 5 segundos
        setInterval(updateDeviceStatuses, 5000);
        
        // Atualizar status inicial
        updateDeviceStatuses();
    </script>
</body>
</html>