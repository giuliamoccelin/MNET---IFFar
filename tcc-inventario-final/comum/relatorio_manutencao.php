<?php
declare(strict_types=1);
require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/security.php';
$usuario = require_login($conexao);

$sql = "SELECT m.tipo_manutencao, m.descricao, m.custo, m.data_registro,
               e.hostname, e.mac, e.patrimonio, u.nome AS tecnico
        FROM manutencao m
        JOIN equipamento e ON e.id = m.id_equipamento
        JOIN usuario u ON u.id = m.id_usuario
        ORDER BY m.data_registro DESC";
$result = mysqli_query($conexao, $sql);
$rows = [];
$total = 0.0;
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    $total += (float) $row['custo'];
}

// Menu conforme o perfil (admin ou usuário)
$arquivoMenu = (($usuario['cargo'] ?? '') === 'A') ? 'menu.php' : 'menu_usuario.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de manutenções | MNET-IFFar</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/MNET-icon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* Fonte padrão do sistema */
        html, body {
            font-family: var(--font-main, 'Poppins', sans-serif);
            color: var(--text-dark, #555);
            background: #fff;
            margin: 0;
            padding: 0;
        }

        /* Visualização na tela */
        .relatorio {
            max-width: 1400px;
            margin: 24px auto;
            background: #fff;
            padding: 24px;
            box-sizing: border-box;
            font-family: var(--font-main, 'Poppins', sans-serif);
        }
        .relatorio-cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .relatorio h1 {
            font-family: var(--font-main, 'Poppins', sans-serif);
            color: var(--primary-color, #233e84);
            font-weight: bold;
            margin: 0 0 4px;
        }
        .relatorio .tabela-relatorio-wrap {
            width: 100%;
            overflow-x: visible;
        }
        .relatorio table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            font-size: 0.85rem;
            font-family: var(--font-main, 'Poppins', sans-serif);
        }
        .relatorio th,
        .relatorio td {
            padding: 8px 10px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
        }
        .relatorio th {
            background-color: var(--primary-color, #233e84);
            color: #fff;
            text-align: left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: var(--font-main, 'Poppins', sans-serif);
        }
        .relatorio td {
            border-bottom: 1px solid #eee;
            color: var(--text-dark, #555);
        }
        .relatorio tr:hover {
            background-color: #e5f6ff;
        }
        .relatorio th:nth-child(1),
        .relatorio td:nth-child(1) { width: 11%; min-width: 90px; }
        .relatorio th:nth-child(2),
        .relatorio td:nth-child(2) { width: 12%; }
        .relatorio th:nth-child(3),
        .relatorio td:nth-child(3) { width: 12%; }
        .relatorio th:nth-child(4),
        .relatorio td:nth-child(4) { width: 12%; min-width: 110px; }
        .relatorio th:nth-child(5),
        .relatorio td:nth-child(5) { width: 9%; }
        .relatorio th:nth-child(6),
        .relatorio td:nth-child(6) { width: 10%; }
        .relatorio th:nth-child(7),
        .relatorio td:nth-child(7) { width: 24%; }
        .relatorio th:nth-child(8),
        .relatorio td:nth-child(8) { width: 10%; white-space: nowrap; text-align: right; }

        .relatorio .btn-salvar {
            width: auto;
            padding: 10px 18px;
            font-family: var(--font-main, 'Poppins', sans-serif);
        }

        /* Impressão: só o conteúdo do relatório (sem menu/topo) */
        @page {
            size: A4 landscape;
            margin: 12mm;
        }
        @media print {
            .nao-imprimir,
            .topbar,
            .topbar + hr,
            nav,
            hr {
                display: none !important;
            }
            html, body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #fff;
                font-family: var(--font-main, 'Poppins', sans-serif);
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .relatorio {
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .relatorio-cabecalho {
                margin-bottom: 8px;
            }
            .relatorio h1 {
                font-size: 1.25rem;
                margin: 0 0 4px;
            }
            .relatorio p {
                margin: 4px 0 10px;
                font-size: 0.9rem;
            }
            .relatorio .tabela-relatorio-wrap {
                overflow: visible !important;
            }
            .relatorio table {
                width: 100% !important;
                font-size: 0.72rem;
                page-break-inside: auto;
            }
            .relatorio thead {
                display: table-header-group;
            }
            .relatorio tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            .relatorio th,
            .relatorio td {
                padding: 5px 6px;
                border-bottom: 1px solid #ccc;
            }
            .relatorio th {
                background-color: #233e84 !important;
                color: #fff !important;
            }
            .relatorio tr:hover {
                background: transparent;
            }
        }
    </style>
</head>
<body>
    <!-- Menu e topo: visíveis na tela, ocultos na impressão -->
    <div class="nao-imprimir">
        <?php include __DIR__ . '/../includes/' . $arquivoMenu; ?>
    </div>

    <main class="relatorio">
        <div class="relatorio-cabecalho">
            <div>
                <h1>Relatório de manutenções</h1>
                <p>Gerado em <?= e(date('d/m/Y H:i')) ?></p>
            </div>
            <button type="button" class="btn-salvar nao-imprimir" onclick="window.print()">
                Imprimir ou salvar em PDF
            </button>
        </div>
        <p><b><?= count($rows) ?></b> registro(s). Custo total: <b>R$ <?= e(number_format($total, 2, ',', '.')) ?></b>.</p>
        <div class="tabela-relatorio-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Técnico</th>
                        <th>Equipamento</th>
                        <th>MAC</th>
                        <th>Patrimônio</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Custo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row) { ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime($row['data_registro']))) ?></td>
                        <td><?= e($row['tecnico']) ?></td>
                        <td><?= e($row['hostname']) ?></td>
                        <td><?= e($row['mac']) ?></td>
                        <td><?= e($row['patrimonio']) ?></td>
                        <td><?= e($row['tipo_manutencao']) ?></td>
                        <td><?= e($row['descricao']) ?></td>
                        <td>R$ <?= e(number_format((float) $row['custo'], 2, ',', '.')) ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>