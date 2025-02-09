<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erro_login = "Email ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema WhatsApp</title>
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

        .login-container {
            max-width: 500px;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2e384d;
        }

        .login-container .form-label {
            color: #4e5d78;
            font-weight: 600;
        }

        .login-container .form-control {
            border-radius: 8px;
            border-color: #ced4da;
        }

        .login-container .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .login-container .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .login-container .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .login-container .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .login-container .mt-3 {
            text-align: center;
        }

        .login-container .mt-3 a {
            color: #007bff;
            text-decoration: none;
        }

        .login-container .mt-3 a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2><i class="fas fa-sign-in-alt me-2"></i> Login</h2>

            <?php if (isset($erro_login)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro_login); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>

                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-2"></i> Entrar</button>
            </form>

            <p class="mt-3">Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a>.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>