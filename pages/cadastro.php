<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro_cadastro = "Todos os campos são obrigatórios.";
    } elseif ($senha !== $confirmar_senha) {
        $erro_cadastro = "As senhas não coincidem.";
    } else {
        // Verifica se o email já está cadastrado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $erro_cadastro = "Este email já está cadastrado.";
        } else {
            // Cria o hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere o novo usuário no banco de dados
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $email, $senha_hash])) {
                $sucesso_cadastro = "Cadastro realizado com sucesso! <a href='login.php'>Clique aqui para fazer login</a>.";
            } else {
                $erro_cadastro = "Erro ao cadastrar usuário. Tente novamente mais tarde.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - Sistema WhatsApp</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f7f9fc;
            color: #364a63;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .cadastro-container {
            max-width: 500px;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
        }

        .cadastro-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2e384d;
        }

        .cadastro-container .form-label {
            color: #4e5d78;
            font-weight: 600;
        }

        .cadastro-container .form-control {
            border-radius: 8px;
            border-color: #ced4da;
        }

        .cadastro-container .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .cadastro-container .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .cadastro-container .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .cadastro-container .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .cadastro-container .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .cadastro-container .mt-3 {
            text-align: center;
        }

        .cadastro-container .mt-3 a {
            color: #007bff;
            text-decoration: none;
        }

        .cadastro-container .mt-3 a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cadastro-container">
            <h2><i class="fas fa-user-plus me-2"></i> Cadastro de Usuário</h2>

            <?php if (isset($erro_cadastro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro_cadastro); ?></div>
            <?php endif; ?>

            <?php if (isset($sucesso_cadastro)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($sucesso_cadastro); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome:</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>

                <div class="mb-3">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha:</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                </div>

                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-user-plus me-2"></i> Cadastrar</button>
            </form>

            <p class="mt-3">Já tem uma conta? <a href="login.php">Faça login aqui</a>.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>