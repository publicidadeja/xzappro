<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Processar exclusão de usuário
if (isset($_POST['excluir_usuario'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$_POST['usuario_id']]);
        $_SESSION['mensagem'] = "Usuário excluído com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao excluir usuário: " . $e->getMessage();
    }
    header('Location: usuarios.php');
    exit;
}

// Processar adição/edição de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $plano_id = $_POST['plano_id'];
    $status = $_POST['status'];

    try {
        if ($_POST['acao'] === 'adicionar') {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, plano_id, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $senha, $telefone, $plano_id, $status]);
            $_SESSION['mensagem'] = "Usuário adicionado com sucesso!";
        } else if ($_POST['acao'] === 'editar') {
            $usuario_id = $_POST['usuario_id'];
            if (!empty($_POST['senha'])) {
                $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, telefone = ?, plano_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$nome, $email, $senha, $telefone, $plano_id, $status, $usuario_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, plano_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$nome, $email, $telefone, $plano_id, $status, $usuario_id]);
            }
            $_SESSION['mensagem'] = "Usuário atualizado com sucesso!";
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao processar usuário: " . $e->getMessage();
    }
    header('Location: usuarios.php');
    exit;
}

// Buscar planos para o select
$planos = $pdo->query("SELECT * FROM planos ORDER BY nome")->fetchAll();

// Listar usuários com informações do plano
$usuarios = $pdo->query("
    SELECT u.*, p.nome as plano_nome 
    FROM usuarios u 
    LEFT JOIN planos p ON u.plano_id = p.id 
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Painel Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
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
                <h2 class="mb-4">Gerenciar Usuários</h2>

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

                <!-- Botão Adicionar Usuário -->
                <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalUsuario" onclick="limparFormulario()">
                    <i class="fas fa-plus"></i> Adicionar Usuário
                </button>

                <!-- Tabela de Usuários -->
                <div class="table-responsive">
                    <table id="tabelaUsuarios" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Plano</th>
                                <th>Status</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['plano_nome']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $usuario['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($usuario['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $usuario['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Usuário -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Adicionar Usuário</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formUsuario" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" id="acao" value="adicionar">
                        <input type="hidden" name="usuario_id" id="usuario_id">
                        
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" name="nome" id="nome" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="senha" id="senha" class="form-control">
                            <small class="form-text text-muted">Deixe em branco para manter a senha atual (ao editar)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" name="telefone" id="telefone" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Plano</label>
                            <select name="plano_id" id="plano_id" class="form-control" required>
                                <?php foreach ($planos as $plano): ?>
                                    <option value="<?php echo $plano['id']; ?>">
                                        <?php echo htmlspecialchars($plano['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
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

    <!-- Modal Confirmação de Exclusão -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir este usuário?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="usuario_id" id="excluir_usuario_id">
                        <input type="hidden" name="excluir_usuario" value="1">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#tabelaUsuarios').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/Portuguese-Brasil.json"
                }
            });
        });

        function limparFormulario() {
            $('#formUsuario')[0].reset();
            $('#acao').val('adicionar');
            $('#usuario_id').val('');
            $('#modalTitle').text('Adicionar Usuário');
            $('#senha').prop('required', true);
        }

        function editarUsuario(usuario) {
            $('#acao').val('editar');
            $('#usuario_id').val(usuario.id);
            $('#nome').val(usuario.nome);
            $('#email').val(usuario.email);
            $('#telefone').val(usuario.telefone);
            $('#plano_id').val(usuario.plano_id);
            $('#status').val(usuario.status);
            $('#senha').prop('required', false);
            $('#modalTitle').text('Editar Usuário');
            $('#modalUsuario').modal('show');
        }

        function confirmarExclusao(usuarioId) {
            $('#excluir_usuario_id').val(usuarioId);
            $('#modalConfirmacao').modal('show');
        }
    </script>
</body>
</html>