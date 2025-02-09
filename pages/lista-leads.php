<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';
include '../includes/functions.php';

// Parâmetros de filtro
$filtros = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'status' => $_GET['status'] ?? '',
    'busca' => $_GET['busca'] ?? '',
    'ordenacao' => $_GET['ordenacao'] ?? 'data_desc'
];

// Construir a query base
$query = "SELECT l.*, d.nome as dispositivo_nome 
          FROM leads_enviados l 
          LEFT JOIN dispositivos d ON l.dispositivo_id = d.device_id 
          WHERE l.usuario_id = :usuario_id";
$params = ['usuario_id' => $_SESSION['usuario_id']];

// Aplicar filtros
if (!empty($filtros['data_inicio'])) {
    $query .= " AND l.data_envio >= :data_inicio";
    $params['data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
}

if (!empty($filtros['data_fim'])) {
    $query .= " AND l.data_envio <= :data_fim";
    $params['data_fim'] = $filtros['data_fim'] . ' 23:59:59';
}

if (!empty($filtros['status'])) {
    $query .= " AND l.status = :status";
    $params['status'] = $filtros['status'];
}

if (!empty($filtros['busca'])) {
    $query .= " AND (l.nome LIKE :busca OR l.numero LIKE :busca)";
    $params['busca'] = '%' . $filtros['busca'] . '%';
}

// Ordenação
switch ($filtros['ordenacao']) {
    case 'nome_asc':
        $query .= " ORDER BY l.nome ASC";
        break;
    case 'nome_desc':
        $query .= " ORDER BY l.nome DESC";
        break;
    case 'data_asc':
        $query .= " ORDER BY l.data_envio ASC";
        break;
    default:
        $query .= " ORDER BY l.data_envio DESC";
}

// Executar a query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = [
    'total_leads' => count($leads),
    'leads_hoje' => 0,
    'leads_mes' => 0
];

// Calcular estatísticas
$hoje = date('Y-m-d');
$inicio_mes = date('Y-m-01');
foreach ($leads as $lead) {
    $data_lead = date('Y-m-d', strtotime($lead['data_envio']));
    if ($data_lead === $hoje) {
        $stats['leads_hoje']++;
    }
    if ($data_lead >= $inicio_mes) {
        $stats['leads_mes']++;
    }
}

// Função para exportar CSV atualizada
function exportToCsv($leads) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    
    fputcsv($output, [
        'Nome', 'Número', 'Data de Envio', 'Status', 
        'Dispositivo', 'Mensagem Enviada', 'Observações'
    ]);
    
    foreach ($leads as $lead) {
        fputcsv($output, [
            $lead['nome'],
            $lead['numero'],
            date('d/m/Y H:i:s', strtotime($lead['data_envio'])),
            $lead['status'],
            $lead['dispositivo_nome'],
            $lead['mensagem'],
            $lead['observacoes'] ?? ''
        ]);
    }
    
    fclose($output);
}

// Processar exportação
if (isset($_POST['export'])) {
    exportToCsv($leads);
    exit;
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $lead_id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM leads_enviados WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$lead_id, $_SESSION['usuario_id']]);
    $_SESSION['mensagem'] = "Lead excluído com sucesso!";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Processar atualização de observações
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_obs'])) {
    $lead_id = $_POST['lead_id'];
    $observacoes = $_POST['observacoes'];
    $stmt = $pdo->prepare("UPDATE leads_enviados SET observacoes = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$observacoes, $lead_id, $_SESSION['usuario_id']]);
    $_SESSION['mensagem'] = "Observações atualizadas com sucesso!";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads Enviados - ZapLocal</title>
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

        /* Estatísticas */
        .stats-card {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card h5 {
            color: var(--text-color);
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Seção de filtros */
        .filter-section {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .filter-section .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .filter-section .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
        }

        .filter-section .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb), 0.25);
        }

        /* Tabela de leads */
        .table-container {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .table {
            width: 100%;
            margin-bottom: 0;
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

        /* Badges de status */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .bg-success {
            background-color: var(--success-color) !important;
        }

        .bg-warning {
            background-color: var(--warning-color) !important;
        }

        /* Botões de ação */
        .lead-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: #fff;
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: #fff;
        }

        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #fff;
        }

        /* Modal de observações */
        .modal-obs {
            max-width: 500px;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        /* Alertas */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 2rem 0;
            margin-top: 2rem;
            color: #6c757d;
            border-top: 1px solid var(--border-color);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .filter-section .col-md-3,
            .filter-section .col-md-9 {
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .lead-actions {
                flex-direction: column;
            }
            
            .lead-actions .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }

        /* Animações */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Hover effects */
        .table tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }

        .stats-card:hover .stats-number {
            color: var(--primary-hover);
        }

        /* Acessibilidade */
        .btn:focus,
        .form-control:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
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
        <header class="dashboard-header">
            <h1><i class="fas fa-address-book me-2"></i> Gestão de Leads</h1>
            <p>Gerencie e analise seus leads de forma eficiente.</p>
        </header>

        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <div class="col-md-9">
                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Total de Leads</h5>
                            <div class="stats-number"><?php echo $stats['total_leads']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Leads Hoje</h5>
                            <div class="stats-number"><?php echo $stats['leads_hoje']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Leads este Mês</h5>
                            <div class="stats-number"><?php echo $stats['leads_mes']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Data Início</label>
                            <input type="date" name="data_inicio" class="form-control" value="<?php echo $filtros['data_inicio']; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data Fim</label>
                            <input type="date" name="data_fim" class="form-control" value="<?php echo $filtros['data_fim']; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="ENVIADO" <?php echo $filtros['status'] == 'ENVIADO' ? 'selected' : ''; ?>>Enviado</option>
                                <option value="PENDENTE" <?php echo $filtros['status'] == 'PENDENTE' ? 'selected' : ''; ?>>Pendente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ordenar por</label>
                            <select name="ordenacao" class="form-control">
                                <option value="data_desc">Data ↓</option>
                                <option value="data_asc" <?php echo $filtros['ordenacao'] == 'data_asc' ? 'selected' : ''; ?>>Data ↑</option>
                                <option value="nome_asc" <?php echo $filtros['ordenacao'] == 'nome_asc' ? 'selected' : ''; ?>>Nome A-Z</option>
                                <option value="nome_desc" <?php echo $filtros['ordenacao'] == 'nome_desc' ? 'selected' : ''; ?>>Nome Z-A</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="busca" class="form-control" placeholder="Buscar por nome ou número..." value="<?php echo htmlspecialchars($filtros['busca']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"> </label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Leads -->
                <div class="table-container">
                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo htmlspecialchars($_SESSION['mensagem']); 
                            unset($_SESSION['mensagem']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2><i class="fas fa-users me-2"></i> Leads</h2>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="export" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i> Exportar CSV
                            </button>
                        </form>
                    </div>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Número</th>
                                <th>Data de Envio</th>
                                <th>Status</th>
                                <th>Dispositivo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lead['nome']); ?></td>
                                <td><?php echo htmlspecialchars($lead['numero']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($lead['data_envio'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $lead['status'] == 'ENVIADO' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars($lead['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($lead['dispositivo_nome']); ?></td>
                                <td class="lead-actions">
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#obsModal<?php echo $lead['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="delete_id" value="<?php echo $lead['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este lead?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal de Observações -->
                            <div class="modal fade" id="obsModal<?php echo $lead['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-obs">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Observações - <?php echo htmlspecialchars($lead['nome']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Observações:</label>
                                                    <textarea name="observacoes" class="form-control" rows="4"><?php echo htmlspecialchars($lead['observacoes'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                <button type="submit" name="update_obs" class="btn btn-primary">Salvar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="dashboard-footer">
            <p>© <?php echo date("Y"); ?> Publicidade Já - Todos os direitos reservados.</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>