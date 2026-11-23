<?php
declare(strict_types=1);


require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/security.php';
$usuarioAtual = require_admin($conexao);
$dashboardAdmin = true;
define('MNET_DASHBOARD_CONTEXT', true);
//cria uma constante sem mudar o valor (true)
require __DIR__ . '/../includes/dashboard_data.php';
$dashboard = load_dashboard_data($conexao, $dashboardAdmin);
//
require __DIR__ . '/../includes/dashboard_view.php';
