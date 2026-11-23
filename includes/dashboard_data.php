<?php
declare(strict_types=1);

if (!defined('MNET_DASHBOARD_CONTEXT')) {
    http_response_code(403);
    exit('Acesso negado.');
}

function dashboard_row(mysqli $conexao, string $sql): array
{
    $result = mysqli_query($conexao, $sql);
    if (!$result) {
        error_log('MNET - consulta do dashboard: ' . mysqli_error($conexao));
        throw new RuntimeException('Não foi possível carregar os indicadores do dashboard.');
    }
    return mysqli_fetch_assoc($result) ?: [];
}

function load_dashboard_data(mysqli $conexao, bool $includeUsers): array
{
    $equipment = dashboard_row($conexao, "SELECT COUNT(*) AS total,
        SUM(CASE WHEN atividade = 'Em uso' THEN 1 ELSE 0 END) AS em_uso,
        SUM(CASE WHEN atividade = 'Disponível' THEN 1 ELSE 0 END) AS disponivel,
        SUM(CASE WHEN atividade = 'Manutenção' THEN 1 ELSE 0 END) AS manutencao,
        SUM(CASE WHEN atividade = 'Descarte' THEN 1 ELSE 0 END) AS descarte,
        SUM(CASE WHEN atividade IS NULL OR atividade = '' THEN 1 ELSE 0 END) AS sem_status,
        SUM(CASE WHEN ipv4 IS NULL OR TRIM(ipv4) = '' THEN 1 ELSE 0 END) AS sem_ip,
        SUM(CASE WHEN mac IS NULL OR TRIM(mac) = '' OR mac = 'xxxxx' THEN 1 ELSE 0 END) AS sem_mac,
        SUM(CASE WHEN patrimonio IS NULL OR TRIM(patrimonio) = '' OR patrimonio = '---' THEN 1 ELSE 0 END) AS sem_patrimonio
        FROM equipamento");

    $maintenance = dashboard_row($conexao, "SELECT COUNT(*) AS total,
        DATE_FORMAT(CURDATE(), '%m/%Y') AS referencia_mes,
        SUM(CASE WHEN data_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            AND data_registro < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
            THEN 1 ELSE 0 END) AS mes,
        SUM(CASE WHEN data_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            AND data_registro < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
            THEN COALESCE(custo, 0) ELSE 0 END) AS custo_mes
        FROM manutencao");

    $recent = [];
    $result = mysqli_query($conexao, "SELECT m.data_registro, m.tipo_manutencao,
        e.hostname, e.ipv4
        FROM manutencao m LEFT JOIN equipamento e ON e.id = m.id_equipamento
        ORDER BY m.data_registro DESC, m.id DESC LIMIT 5");
    if (!$result) {
        error_log('MNET - manutenções recentes: ' . mysqli_error($conexao));
        throw new RuntimeException('Não foi possível carregar as manutenções recentes.');
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $recent[] = $row;
    }

    $users = null;
    if ($includeUsers) {
        $users = dashboard_row($conexao, "SELECT COUNT(*) AS total,
            SUM(CASE WHEN cargo = 'A' THEN 1 ELSE 0 END) AS administradores,
            SUM(CASE WHEN cargo = 'U' THEN 1 ELSE 0 END) AS comuns FROM usuario");
    }
    return ['equipment' => $equipment, 'maintenance' => $maintenance,
        'recent' => $recent, 'users' => $users];
}
