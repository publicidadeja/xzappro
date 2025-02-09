<?php
// Definir a URL base dinamicamente
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    $base_url .= '/xzappro'; // Adiciona o diretório do projeto se estiver em localhost
}
?>

<div class="col-md-3">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo $base_url; ?>/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo $base_url; ?>/pages/enviar-mensagem.php"><i class="fas fa-envelope"></i> Enviar Mensagem</a></li>
            <li><a href="<?php echo $base_url; ?>/pages/lista-leads.php"><i class="fas fa-address-book"></i> Listar Leads</a></li>
            <li><a href="<?php echo $base_url; ?>/pages/dispositivos.php"><i class="fas fa-mobile-alt"></i> Dispositivos</a></li>
            <li><a href="<?php echo $base_url; ?>/pages/envio-massa.php"><i class="fas fa-rocket"></i> Envio em Massa</a></li>
            <li><a href="<?php echo $base_url; ?>/pages/configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
            <li><a href="<?php echo $base_url; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </div>
</div>