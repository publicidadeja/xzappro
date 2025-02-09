<?php

// Mantendo todo o código PHP original no topo
session_start();
include '../includes/auth.php';
redirecionarSeNaoLogado();
include '../includes/db.php';


// Consultas originais mantidas
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_leads FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$total_leads = $stmt->fetch()['total_leads'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_envios_massa FROM envios_em_massa WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$total_envios_massa = $stmt->fetch()['total_envios_massa'];

$stmt = $pdo->prepare("SELECT MAX(data_envio) AS ultimo_envio FROM envios_em_massa WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$ultimo_envio = $stmt->fetch()['ultimo_envio'];

$stmt = $pdo->prepare("SELECT nome, numero FROM leads_enviados WHERE usuario_id = ? ORDER BY data_envio DESC LIMIT 1");
$stmt->execute([$_SESSION['usuario_id']]);
$ultimo_lead = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ZapLocal</title>
    
    <!-- CSS Moderno -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
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

        /* Conteúdo Principal */
        .main-content {
            padding: 2rem;
        }

        /* Dashboard Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            color: var(--text-color);
        }

        .welcome-text {
            font-size: 1.25rem;
            color: var(--text-secondary);
        }

        /* Cards de Métricas */
        .metric-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 1rem 0;
        }

        .metric-description {
            color: var(--text-secondary);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .welcome-text {
                margin-top: 0.5rem;
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
                <div class="main-content">
                    <div class="dashboard-header">
                        <div>
                            <h1>Dashboard</h1>
                            <p class="welcome-text">Bem-vindo(a), <?php echo htmlspecialchars($usuario['nome']); ?></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="metric-card">
                                <h3>Total de Leads</h3>
                                <div class="metric-value"><?php echo number_format($total_leads); ?></div>
                                <p class="metric-description"><i class="fas fa-users"></i> Leads cadastrados</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="metric-card">
                                <h3>Envios em Massa</h3>
                                <div class="metric-value"><?php echo number_format($total_envios_massa); ?></div>
                                <p class="metric-description"><i class="fas fa-paper-plane"></i> Campanhas realizadas</p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="metric-card">
                                <h3>Último Envio</h3>
                                <div class="metric-value">
                                    <?php echo $ultimo_envio ? date('d/m/Y H:i', strtotime($ultimo_envio)) : '-'; ?>
                                </div>
                                <p class="metric-description"><i class="fas fa-clock"></i> Data do último envio</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="metric-card">
                                <h3>Lead Recente</h3>
                                <div class="metric-value">
                                    <?php echo $ultimo_lead ? htmlspecialchars($ultimo_lead['nome']) : '-'; ?>
                                </div>
                                <p class="metric-description"><i class="fas fa-user"></i> Último lead cadastrado</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>