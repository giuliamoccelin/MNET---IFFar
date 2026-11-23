<?php
declare(strict_types=1);

require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/security.php';
$usuarioAtual = require_login($conexao);
$dashboardAdmin = ($usuarioAtual['cargo'] ?? '') === 'A';
define('MNET_DASHBOARD_CONTEXT', true);
require __DIR__ . '/../includes/dashboard_data.php';
$dashboard = load_dashboard_data($conexao, $dashboardAdmin);
require __DIR__ . '/../includes/dashboard_view.php';
