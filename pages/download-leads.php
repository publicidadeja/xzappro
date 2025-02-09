<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';



// Consulta para obter os leads enviados pelo usuário logado
$stmt = $pdo->prepare("SELECT numero, mensagem, data_envio FROM leads_enviados WHERE usuario_id = ? ORDER BY data_envio DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($leads) > 0) {
    // Define o cabeçalho para download de arquivo CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="leads_enviados.csv"');

    // Abre o output para escrita
    $output = fopen('php://output', 'w');

    // Escreve o cabeçalho do CSV
    fputcsv($output, ['Número', 'Mensagem', 'Data de Envio']);

    // Escreve os dados dos leads
    foreach ($leads as $lead) {
        fputcsv($output, [$lead['numero'], $lead['mensagem'], $lead['data_envio']]);
    }

    fclose($output);
    exit;
} else {
    echo "<p>Nenhum lead disponível para download.</p>";
    echo "<a href='lista-leads.php'>Voltar</a>";
}
?>