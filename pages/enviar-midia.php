<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_raw = $_POST['numero'];
    $numero = preg_replace('/[^0-9]/', '', $numero_raw);
    $nome = trim($_POST['nome']);

    if (empty($numero) || strlen($numero) < 10) {
        echo "<p class='text-danger'>Número inválido</p>";
    } elseif (empty($_FILES['arquivo']['name'])) {
        echo "<p class='text-danger'>Selecione um arquivo para enviar</p>";
    } else {
        // Nova implementação usando a API local
        $arquivo_path = $_FILES['arquivo']['tmp_name'];
        
        // Primeiro envia o arquivo
        $data = [
            'deviceId' => $usuario['token_dispositivo'],
            'number' => $numero,
            'file' => new CURLFile($arquivo_path)
        ];

        $ch = curl_init('http://localhost:3000/message/send-file');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            echo "<p class='text-success'>Arquivo enviado com sucesso!</p>";
        } else {
            echo "<p class='text-danger'>Erro ao enviar arquivo</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Mídia</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .input-group-text {
            background-color: #0d6efd;
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2><i class="fas fa-image me-2"></i>Enviar Mídia</h2>
        <a href="dashboard.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3 input-group">
                <span class="input-group-text">+55</span>
                <input type="text" name="numero" id="numero" class="form-control" placeholder="Digite o número (com DDD)" required>
            </div>

            <div class="mb-3">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" name="nome" id="nome" class="form-control" placeholder="Digite o nome" required>
            </div>

            <div class="mb-3">
                <label for="arquivo" class="form-label">Selecione o Arquivo:</label>
                <input type="file" name="arquivo" id="arquivo" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-upload me-2"></i>Enviar Mídia</button>
        </form>
    </div>

    <!-- Máscara para o campo de número -->
    <script>
        $(document).ready(function() {
            $('#numero').inputmask('(99) 99999-9999'); // Máscara para números brasileiros
        });
    </script>
</body>
</html>