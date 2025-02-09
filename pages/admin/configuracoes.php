<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Processar formulário de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Atualizar configurações gerais
        $stmt = $pdo->prepare("UPDATE configuracoes SET 
            nome_site = ?,
            email_suporte = ?,
            whatsapp_suporte = ?,
            tempo_entre_envios = ?,
            max_leads_dia = ?,
            max_mensagens_dia = ?,
            termos_uso = ?,
            politica_privacidade = ?
            WHERE id = 1");
            
        $stmt->execute([
            $_POST['nome_site'],
            $_POST['email_suporte'],
            $_POST['whatsapp_suporte'],
            $_POST['tempo_entre_envios'],
            $_POST['max_leads_dia'],
            $_POST['max_mensagens_dia'],
            $_POST['termos_uso'],
            $_POST['politica_privacidade']
        ]);

        $_SESSION['mensagem'] = "Configurações atualizadas com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar configurações: " . $e->getMessage();
    }
    
    header('Location: configuracoes.php');
    exit;
}

// Buscar configurações atuais
$config = $pdo->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Painel Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
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
        .config-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .config-section h4 {
            color: #343a40;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
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
                <h2 class="mb-4">Configurações do Sistema</h2>

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

                <form method="POST">
                    <!-- Configurações Gerais -->
                    <div class="config-section">
                        <h4><i class="fas fa-cogs"></i> Configurações Gerais</h4>
                        <div class="form-group">
                            <label>Nome do Site</label>
                            <input type="text" name="nome_site" class="form-control" value="<?php echo htmlspecialchars($config['nome_site']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email de Suporte</label>
                            <input type="email" name="email_suporte" class="form-control" value="<?php echo htmlspecialchars($config['email_suporte']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>WhatsApp de Suporte</label>
                            <input type="text" name="whatsapp_suporte" class="form-control" value="<?php echo htmlspecialchars($config['whatsapp_suporte']); ?>" required>
                        </div>
                    </div>

                    <!-- Limites e Restrições -->
                    <div class="config-section">
                        <h4><i class="fas fa-shield-alt"></i> Limites e Restrições</h4>
                        <div class="form-group">
                            <label>Tempo Entre Envios (segundos)</label>
                            <input type="number" name="tempo_entre_envios" class="form-control" value="<?php echo htmlspecialchars($config['tempo_entre_envios']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Máximo de Leads por Dia</label>
                            <input type="number" name="max_leads_dia" class="form-control" value="<?php echo htmlspecialchars($config['max_leads_dia']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Máximo de Mensagens por Dia</label>
                            <input type="number" name="max_mensagens_dia" class="form-control" value="<?php echo htmlspecialchars($config['max_mensagens_dia']); ?>" required>
                        </div>
                    </div>

                    <!-- Termos e Políticas -->
                    <div class="config-section">
                        <h4><i class="fas fa-file-contract"></i> Termos e Políticas</h4>
                        <div class="form-group">
                            <label>Termos de Uso</label>
                            <textarea name="termos_uso" id="termos_uso" class="form-control"><?php echo htmlspecialchars($config['termos_uso']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Política de Privacidade</label>
                            <textarea name="politica_privacidade" id="politica_privacidade" class="form-control"><?php echo htmlspecialchars($config['politica_privacidade']); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar o editor de texto rico para os campos de termos e políticas
            $('#termos_uso, #politica_privacidade').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                lang: 'pt-BR'
            });
        });
    </script>
</body>
</html>