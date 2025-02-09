<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nome = $_POST['nome'];
                $preco = $_POST['preco'];
                $descricao = $_POST['descricao'];
                $recursos = isset($_POST['recursos']) ? json_encode($_POST['recursos']) : '[]';
                $limite_leads = $_POST['limite_leads'];
                $limite_mensagens = $_POST['limite_mensagens'];
                
                $stmt = $pdo->prepare("INSERT INTO planos (nome, preco, descricao, recursos, limite_leads, limite_mensagens, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$nome, $preco, $descricao, $recursos, $limite_leads, $limite_mensagens]);
                $_SESSION['mensagem'] = "Plano adicionado com sucesso!";
                break;

            case 'edit':
                $id = $_POST['id'];
                $nome = $_POST['nome'];
                $preco = $_POST['preco'];
                $descricao = $_POST['descricao'];
                $recursos = isset($_POST['recursos']) ? json_encode($_POST['recursos']) : '[]';
                $limite_leads = $_POST['limite_leads'];
                $limite_mensagens = $_POST['limite_mensagens'];
                
                $stmt = $pdo->prepare("UPDATE planos SET nome = ?, preco = ?, descricao = ?, recursos = ?, limite_leads = ?, limite_mensagens = ? WHERE id = ?");
                $stmt->execute([$nome, $preco, $descricao, $recursos, $limite_leads, $limite_mensagens, $id]);
                $_SESSION['mensagem'] = "Plano atualizado com sucesso!";
                break;

            case 'delete':
                $id = $_POST['id'];
                // Verificar se existem usuários usando este plano
                $usuarios = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE plano_id = ?");
                $usuarios->execute([$id]);
                if ($usuarios->fetchColumn() > 0) {
                    $_SESSION['erro'] = "Não é possível excluir este plano pois existem usuários vinculados a ele.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM planos WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['mensagem'] = "Plano excluído com sucesso!";
                }
                break;
        }
        header('Location: planos.php');
        exit;
    }
}

// Buscar planos
$planos = $pdo->query("SELECT * FROM planos ORDER BY preco ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Planos - Painel Admin</title>
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
        .plan-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
                <h2 class="mb-4">Gerenciar Planos</h2>

                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['mensagem'];
                        unset($_SESSION['mensagem']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['erro'];
                        unset($_SESSION['erro']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Botão Adicionar Plano -->
                <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addPlanModal">
                    <i class="fas fa-plus"></i> Adicionar Plano
                </button>

                <!-- Lista de Planos -->
                <div class="row">
                    <?php foreach ($planos as $plano): ?>
                        <div class="col-md-4">
                            <div class="card plan-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($plano['nome']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?>
                                    </h6>
                                    <p class="card-text"><?php echo htmlspecialchars($plano['descricao']); ?></p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-users"></i> Limite de Leads: <?php echo $plano['limite_leads']; ?></li>
                                        <li><i class="fas fa-envelope"></i> Limite de Mensagens: <?php echo $plano['limite_mensagens']; ?></li>
                                    </ul>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info" onclick="editPlan(<?php echo htmlspecialchars(json_encode($plano)); ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deletePlan(<?php echo $plano['id']; ?>)">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Plano -->
    <div class="modal fade" id="addPlanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Plano</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Nome do Plano</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Preço (R$)</label>
                            <input type="number" name="preco" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Limite de Leads</label>
                            <input type="number" name="limite_leads" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Limite de Mensagens</label>
                            <input type="number" name="limite_mensagens" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Plano -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Plano</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-group">
                            <label>Nome do Plano</label>
                            <input type="text" name="nome" id="edit-nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Preço (R$)</label>
                            <input type="number" name="preco" id="edit-preco" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" id="edit-descricao" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Limite de Leads</label>
                            <input type="number" name="limite_leads" id="edit-limite-leads" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Limite de Mensagens</label>
                            <input type="number" name="limite_mensagens" id="edit-limite-mensagens" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form para exclusão -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-id">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        function editPlan(plano) {
            document.getElementById('edit-id').value = plano.id;
            document.getElementById('edit-nome').value = plano.nome;
            document.getElementById('edit-preco').value = plano.preco;
            document.getElementById('edit-descricao').value = plano.descricao;
            document.getElementById('edit-limite-leads').value = plano.limite_leads;
            document.getElementById('edit-limite-mensagens').value = plano.limite_mensagens;
            $('#editPlanModal').modal('show');
        }

        function deletePlan(id) {
            if (confirm('Tem certeza que deseja excluir este plano?')) {
                document.getElementById('delete-id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>