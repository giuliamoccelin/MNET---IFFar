<?php
declare(strict_types=1);

if (!defined('MNET_DASHBOARD_CONTEXT')) {
    http_response_code(403);
    exit('Acesso negado.');
}

$equipment = $dashboard['equipment'];
$maintenance = $dashboard['maintenance'];
$totalEquipment = (int) ($equipment['total'] ?? 0);
$statuses = [
    ['Em uso', (int) ($equipment['em_uso'] ?? 0), 'uso'],
    ['Disponível', (int) ($equipment['disponivel'] ?? 0), 'disponivel'],
    ['Manutenção', (int) ($equipment['manutencao'] ?? 0), 'manutencao'],
    ['Descarte', (int) ($equipment['descarte'] ?? 0), 'descarte'],
    ['Sem situação', (int) ($equipment['sem_status'] ?? 0), 'sem-status'],
];

// Cada perfil usa páginas de pastas diferentes
if ($dashboardAdmin) {
    $arquivoMenu = 'menu.php';
    $linkEquipamentos = BASE_URL . '/admin/listar_maquinas.php';
    $linkManutencoes = BASE_URL . '/admin/listar_manutencao.php';
} else {
    $arquivoMenu = 'menu_usuario.php';
    $linkEquipamentos = BASE_URL . '/usuario/usuario_listar_maquinas.php';
    $linkManutencoes = BASE_URL . '/usuario/usuario_listar_manutencao.php';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head><?php include __DIR__ . '/head.php'; ?></head>
<body>
<?php include __DIR__ . '/' . $arquivoMenu; ?>
<main class="dashboard">
    <header class="dashboard-header">
        <h1>Olá, <?= e($usuarioAtual['nome']) ?></h1>
        <p>Visão atual do inventário cadastrado. Dispositivos não cadastrados só aparecem após uma varredura.</p>
    </header>

    <div class="dashboard-kpis">
        <article class="dashboard-kpi"><span>Equipamentos cadastrados</span><strong><?= $totalEquipment ?></strong><a href="<?= $linkEquipamentos ?>">Ver equipamentos</a></article>
        <article class="dashboard-kpi"><span>Em uso</span><strong><?= (int) ($equipment['em_uso'] ?? 0) ?></strong><small>Equipamentos em operação</small></article>
        <article class="dashboard-kpi"><span>Em manutenção</span><strong><?= (int) ($equipment['manutencao'] ?? 0) ?></strong><small>Conforme situação do cadastro</small></article>
        <article class="dashboard-kpi"><span>Manutenções no mês</span><strong><?= (int) ($maintenance['mes'] ?? 0) ?></strong><small>De <?= e($maintenance['referencia_mes'] ?? '') ?></small></article>
        <?php if ($dashboardAdmin) { ?>
        <article class="dashboard-kpi"><span>Usuários cadastrados</span><strong><?= (int) ($dashboard['users']['total'] ?? 0) ?></strong><small><?= (int) ($dashboard['users']['administradores'] ?? 0) ?> administrador(es) e <?= (int) ($dashboard['users']['comuns'] ?? 0) ?> comum(ns)</small></article>
        <?php } ?>
    </div>

    <div class="dashboard-sections">
        <section class="dashboard-panel">
            <h2>Situação dos equipamentos</h2>
            <?php foreach ($statuses as [$label, $count, $class]) { ?>
                <div class="dashboard-status-row">
                    <span><?= e($label) ?></span><strong><?= $count ?></strong>
                    <div class="dashboard-track" role="img" aria-label="<?= e($label . ': ' . $count . ' de ' . $totalEquipment) ?>">
                        <div class="dashboard-bar status-<?= e($class) ?>" style="width: <?= $totalEquipment > 0 ? round(100 * $count / $totalEquipment, 1) : 0 ?>%"></div>
                    </div>
                </div>
            <?php } ?>
            <?php if ($totalEquipment === 0) { ?><p>Ainda não há equipamentos cadastrados.</p><?php } ?>
        </section>

        <section class="dashboard-panel">
            <h2>Qualidade do inventário</h2>
            <p>Registros que precisam de conferência:</p>
            <dl class="dashboard-quality">
                <div><dt>Sem IP</dt><dd><?= (int) ($equipment['sem_ip'] ?? 0) ?></dd></div>
                <div><dt>Sem MAC ou com placeholder</dt><dd><?= (int) ($equipment['sem_mac'] ?? 0) ?></dd></div>
                <div><dt>Sem patrimônio informado</dt><dd><?= (int) ($equipment['sem_patrimonio'] ?? 0) ?></dd></div>
            </dl>
            <p class="dashboard-note">O painel não afirma que esses dispositivos estão online; mostra apenas os registros do banco.</p>
        </section>

        <section class="dashboard-panel">
            <h2>Manutenções</h2>
            <dl class="dashboard-quality">
                <div><dt>Total registrado</dt><dd><?= (int) ($maintenance['total'] ?? 0) ?></dd></div>
                <div><dt>Custo neste mês</dt><dd>R$ <?= e(number_format((float) ($maintenance['custo_mes'] ?? 0), 2, ',', '.')) ?></dd></div>
            </dl>
            <a href="<?= $linkManutencoes ?>">Ver manutenções</a>
        </section>

        <section class="dashboard-panel">
            <h2>Últimas manutenções</h2>
            <?php if ($dashboard['recent'] === []) { ?>
                <p>Nenhuma manutenção registrada.</p>
            <?php } else { ?>
                <div class="tabela-responsiva"><table class="dashboard-table">
                    <thead><tr><th>Data</th><th>Equipamento</th><th>Tipo</th></tr></thead>
                    <tbody><?php foreach ($dashboard['recent'] as $item) { ?>
                        <tr><td><?= e($item['data_registro'] ? date('d/m/Y', strtotime($item['data_registro'])) : 'N/D') ?></td>
                        <td><?= e($item['hostname'] ?: ($item['ipv4'] ?: 'Equipamento removido')) ?></td>
                        <td><?= e($item['tipo_manutencao'] ?: 'N/D') ?></td></tr>
                    <?php } ?></tbody>
                </table></div>
            <?php } ?>
        </section>
    </div>
    <?php if ($dashboardAdmin) { ?><p class="dashboard-action"><a class="btn-filtrar" href="<?= BASE_URL ?>/admin/escanear_nmap.php">Escanear rede autorizada</a></p><?php } ?>
</main>
</body>
</html>
