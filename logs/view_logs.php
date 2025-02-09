<?php
require_once 'logger.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logs do Claude API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 800px;
            overflow-y: auto;
        }
        .error-log {
            color: #dc3545;
        }
        .success-log {
            color: #198754;
        }
        .info-log {
            color: #0dcaf0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Logs do Claude API</h2>
        
        <div class="mb-3">
            <button class="btn btn-danger btn-sm" onclick="clearLogs()">Limpar Logs</button>
            <button class="btn btn-primary btn-sm" onclick="refreshLogs()">Atualizar</button>
        </div>

        <pre id="logContent"><?php echo htmlspecialchars(Logger::getLogContent()); ?></pre>
    </div>

    <script>
        function clearLogs() {
            if (confirm('Tem certeza que deseja limpar os logs?')) {
                fetch('clear_logs.php')
                    .then(() => refreshLogs());
            }
        }

        function refreshLogs() {
            fetch('get_logs.php')
                .then(response => response.text())
                .then(logs => {
                    document.getElementById('logContent').innerHTML = logs;
                });
        }

        // Atualiza os logs a cada 30 segundos
        setInterval(refreshLogs, 30000);
    </script>
</body>
</html>