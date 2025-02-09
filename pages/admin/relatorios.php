<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';
include '../../includes/functions.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Definir período padrão (último mês)
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Buscar estatísticas gerais
try {
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_usuarios = $stmt->fetch()['total'];

    // Total de leads
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads_enviados");
    $total_leads = $stmt->fetch()['total'];

    // Leads por período
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads_enviados 
        WHERE data_envio BETWEEN ? AND ?");
    $stmt->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $leads_periodo = $stmt->fetch()['total'];

    // Usuários ativos no período
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT usuario_id) as total FROM leads_enviados 
        WHERE data_envio BETWEEN ? AND ?");
    $stmt->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $usuarios_ativos = $stmt->fetch()['total'];

    // Top 10 usuários com mais leads
    $stmt = $pdo->prepare("SELECT u.nome, COUNT(l.id) as total_leads 
        FROM usuarios u 
        LEFT JOIN leads_enviados l ON u.id = l.usuario_id 
        WHERE l.data_envio BETWEEN ? AND ?
        GROUP BY u.id 
        ORDER BY total_leads DESC 
        LIMIT 10");
    $stmt->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $top_usuarios = $stmt->fetchAll();

    // Leads por dia no período
    $stmt = $pdo->prepare("SELECT DATE(data_envio) as dia, COUNT(*) as total 
        FROM leads_enviados 
        WHERE data_envio BETWEEN ? AND ? 
        GROUP BY DATE(data_envio) 
        ORDER BY dia");
    $stmt->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $leads_por_dia = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao buscar relatórios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Painel Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card h3 {
            color: #343a40;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #17a2b8;
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
                        <li><a href="leads.php"><i class="fas fa-address-book"></i> Leads</a></li>
                        <li><a href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
                        <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                    </ul>
                </div>
            </div>

            <!-- Conteúdo -->
            <div class="col-md-9 py-4">
                <h2 class="mb-4">Relatórios</h2>

                <!-- Filtro de Data -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <label class="mr-2">Data Início:</label>
                                <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
                            </div>
                            <div class="form-group mr-3">
                                <label class="mr-2">Data Fim:</label>
                                <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </form>
                    </div>
                </div>

                <!-- Cards de Estatísticas -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Total de Usuários</h3>
                            <div class="stats-number"><?php echo number_format($total_usuarios); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Total de Leads</h3>
                            <div class="stats-number"><?php echo number_format($total_leads); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Leads no Período</h3>
                            <div class="stats-number"><?php echo number_format($leads_periodo); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Usuários Ativos</h3>
                            <div class="stats-number"><?php echo number_format($usuarios_ativos); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Leads por Dia -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Leads por Dia</h3>
                        <canvas id="leadsChart"></canvas>
                    </div>
                </div>

                <!-- Top Usuários -->
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Top 10 Usuários</h3>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Total de Leads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                        <td><?php echo number_format($usuario['total_leads']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Configuração do gráfico de leads
        var ctx = document.getElementById('leadsChart').getContext('2d');
        var leadsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($leads_por_dia, 'dia')); ?>,
                datasets: [{
                    label: 'Leads por Dia',
                    data: <?php echo json_encode(array_column($leads_por_dia, 'total')); ?>,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>