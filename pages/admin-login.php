<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM administradores WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nome'] = $admin['nome'];
        $_SESSION['admin_nivel'] = $admin['nivel_acesso'];
        header('Location: admin/dashboard.php');
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
    <title>Login Administrativo - Sistema WhatsApp</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
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
            background: #2d2d2d;
            border-radius: 12px;
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.2);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #ffffff;
        }

        .login-container .form-label {
            color: #ffffff;
            font-weight: 600;
        }

        .login-container .form-control {
            border-radius: 8px;
            border-color: #404040;
            background-color: #333333;
            color: #ffffff;
        }

        .login-container .form-control:focus {
            border-color: #0056b3;
            box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.25);
            background-color: #404040;
        }

        .login-container .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
            transition: all 0.3s ease;
        }

        .login-container .btn-primary:hover {
            background-color: #003d80;
            border-color: #003d80;
            transform: translateY(-2px);
        }

        .login-container .alert-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .admin-badge {
            background-color: #dc3545;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header i {
            font-size: 3rem;
            color: #0056b3;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h2>Acesso Administrativo <span class="admin-badge">ADMIN</span></h2>
            </div>

            <?php if (isset($erro_login)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($erro_login); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email Administrativo:
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">
                        <i class="fas fa-lock me-2"></i>Senha:
                    </label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Acessar Painel Admin
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-light">
                    <i class="fas fa-arrow-left me-1"></i> Voltar para login normal
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>