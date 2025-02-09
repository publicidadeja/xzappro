<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';

// Consulta para obter os dados atuais do usuário
$stmt = $pdo->prepare("SELECT token_dispositivo, mensagem_base, arquivo_padrao FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Variável para mensagens de status
$mensagem_status = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = trim($_POST['token_dispositivo']);
    $mensagem_base = trim($_POST['mensagem_base']);

    // Processa o upload do arquivo
    if ($_FILES['arquivo_padrao']['error'] == UPLOAD_ERR_OK) {
        $arquivo_tmp = $_FILES['arquivo_padrao']['tmp_name'];
        $arquivo_nome = $_FILES['arquivo_padrao']['name'];
        $arquivo_destino = '../uploads/' . $arquivo_nome; // Pasta para salvar os arquivos

        // Move o arquivo para a pasta de destino
        if (move_uploaded_file($arquivo_tmp, $arquivo_destino)) {
            // Atualiza os dados do usuário no banco de dados
            $stmt = $pdo->prepare("UPDATE usuarios SET token_dispositivo = ?, mensagem_base = ?, arquivo_padrao = ? WHERE id = ?");
            if ($stmt->execute([$token, $mensagem_base, $arquivo_nome, $_SESSION['usuario_id']])) {
                $mensagem_status = "<div class='alert alert-success'>Configurações atualizadas com sucesso!</div>";
            } else {
                $mensagem_status = "<div class='alert alert-danger'>Erro ao atualizar as configurações.</div>";
            }
        } else {
            $mensagem_status = "<div class='alert alert-danger'>Erro ao salvar o arquivo.</div>";
        }
    } else {
        // Atualiza os dados do usuário no banco de dados (sem o arquivo)
        $stmt = $pdo->prepare("UPDATE usuarios SET token_dispositivo = ?, mensagem_base = ? WHERE id = ?");
        if ($stmt->execute([$token, $mensagem_base, $_SESSION['usuario_id']])) {
            $mensagem_status = "<div class='alert alert-success'>Configurações atualizadas com sucesso!</div>";
        } else {
            $mensagem_status = "<div class='alert alert-danger'>Erro ao atualizar as configurações.</div>";
        }
    }

    // Recarrega os dados do usuário após a atualização
    $stmt = $pdo->prepare("SELECT token_dispositivo, mensagem_base, arquivo_padrao FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - ZapLocal</title>
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

        .form-control {
            border-radius: 8px;
            border-color: var(--border-color);
        }

        .form-control:focus {
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
                    <h2 class="form-title"><i class="fas fa-sliders-h me-2"></i> Configurações</h2>

                    <!-- Mensagem de Status -->
                    <?php if ($mensagem_status): ?>
                        <?php echo $mensagem_status; ?>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="mensagem_base" class="form-label">Mensagem Base:</label>
                            <textarea class="form-control" id="mensagem_base" name="mensagem_base" rows="3" placeholder="Insira a mensagem base para personalização"><?php echo htmlspecialchars($usuario['mensagem_base'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Use <code>{nome}</code> para personalizar a mensagem.</small>
                        </div>

                        <div class="mb-3">
                            <label for="arquivo_padrao" class="form-label">Arquivo Padrão:</label>
                            <input type="file" class="form-control" id="arquivo_padrao" name="arquivo_padrao">
                            <?php if (!empty($usuario['arquivo_padrao'])): ?>
                                <small class="form-text text-muted">Arquivo atual: <?php echo htmlspecialchars($usuario['arquivo_padrao']); ?></small>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i> Salvar Configurações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>