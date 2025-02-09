<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Estatísticas
$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_leads = $pdo->query("SELECT COUNT(*) FROM leads_enviados")->fetchColumn();
$total_mensagens = $pdo->query("SELECT COUNT(*) FROM envios_em_massa")->fetchColumn();

// Últimos usuários cadastrados
$ultimos_usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Últimas mensagens enviadas
$ultimas_mensagens = $pdo->query("SELECT * FROM envios_em_massa ORDER BY data_envio DESC LIMIT 5")->fetchAll();

// Total de mensagens por dia (últimos 7 dias)
$stats_mensagens = $pdo->query("
    SELECT DATE(data_envio) as data, COUNT(*) as total 
    FROM envios_em_massa 
    WHERE data_envio >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY DATE(data_envio)
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            padding: 20px 0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            padding: 10px 20px;
        }
        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
        }
        .sidebar ul li a:hover {
            color: #17a2b8;
        }
        .card {
            margin-bottom: 20px;
        }
        .stats-card {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Menu Lateral -->
            <div class="col-md-3">
                <div class="sidebar">
                    <ul>
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                        <li><a href="planos.php"><i class="fas fa-box"></i> Planos</a></li>
                        <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                        <li><a href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
                        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                    </ul>
                </div>
            </div>

            <!-- Conteúdo -->
            <div class="col-md-9 py-4">
                <h2 class="mb-4">Dashboard</h2>
                
                <!-- Cards de Estatísticas -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total de Usuários</h5>
                                <h2><?php echo $total_usuarios; ?></h2>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total de Leads</h5>
                                <h2><?php echo $total_leads; ?></h2>
                                <i class="fas fa-address-book fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total de Mensagens</h5>
                                <h2><?php echo $total_mensagens; ?></h2>
                                <i class="fas fa-envelope fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Usuários -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Últimos Usuários Cadastrados</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Data de Cadastro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimos_usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Últimas Mensagens -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Últimas Mensagens Enviadas</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Mensagem</th>
                                    <th>Data de Envio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_mensagens as $mensagem): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mensagem['usuario_id']); ?></td>
                                    <td><?php echo htmlspecialchars($mensagem['mensagem']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mensagem['data_envio'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>