<?php

declare(strict_types=1);

require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/security.php';
require_admin($conexao);

$msg = '';
$values = [
    'hostname' => trim((string) ($_GET['hostname'] ?? '')),
    'ipv4' => trim((string) ($_GET['ipv4'] ?? '')),
    'mac' => trim((string) ($_GET['mac'] ?? '')),
    'pat' => '',
    'local' => '',
    'so' => trim((string) ($_GET['so'] ?? '')),
    'atividade' => 'Em uso',
    'marca' => trim((string) ($_GET['marca'] ?? '')),
    'tipo_equipamento' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (array_keys($values) as $field) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $allowedStatus = ['Em uso', 'Disponível', 'Descarte', 'Manutenção'];
    if (filter_var($values['ipv4'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        $msg = 'Informe um endereço IPv4 válido, sem máscara CIDR.';
    } elseif (!in_array($values['atividade'], $allowedStatus, true)) {
        $msg = 'Situação do equipamento inválida.';
    } else {
        $macDigits = strtoupper(preg_replace('/[^0-9A-F]/i', '', $values['mac']));
        if ($values['mac'] !== '' && strlen($macDigits) !== 12) {
            $msg = 'O endereço MAC deve conter 12 dígitos hexadecimais ou ficar vazio quando não identificado.';
        } else {
            if ($macDigits !== '') {
                $values['mac'] = implode(':', str_split($macDigits, 2));
            }
            $check = mysqli_prepare($conexao, "SELECT id FROM equipamento WHERE ipv4 = ? OR (? <> '' AND mac = ?) LIMIT 1");
            mysqli_stmt_bind_param($check, 'sss', $values['ipv4'], $values['mac'], $values['mac']);
            mysqli_stmt_execute($check);
            $duplicate = mysqli_stmt_get_result($check)->num_rows > 0;
            mysqli_stmt_close($check);

            if ($duplicate) {
                $msg = 'Já existe um equipamento com este IPv4 ou MAC.';
            } else {
                $sql = 'INSERT INTO equipamento (hostname, ipv4, mac, patrimonio, local, sistema_operacional, atividade, marca, tipo_equipamento)
                        VALUES (NULLIF(?, \'\'), ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), ?, NULLIF(?, \'\'), NULLIF(?, \'\'))';
                $stmt = mysqli_prepare($conexao, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    'sssssssss',
                    $values['hostname'],
                    $values['ipv4'],
                    $values['mac'],
                    $values['pat'],
                    $values['local'],
                    $values['so'],
                    $values['atividade'],
                    $values['marca'],
                    $values['tipo_equipamento']
                );
                if (mysqli_stmt_execute($stmt)) {
                    header('Location: listar_maquinas.php?msg=' . rawurlencode('Equipamento cadastrado com sucesso.'));
                    exit();
                }
                error_log('MNET - falha ao cadastrar equipamento: ' . mysqli_stmt_error($stmt));
                $msg = 'Não foi possível cadastrar o equipamento.';
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head><?php include __DIR__ . '/../includes/head.php'; ?></head>

<body>
    <?php include __DIR__ . '/../includes/menu.php'; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="fundo-perfil">
            <div class="card-perfil">
                <div class="titulo">
                    <h2>Cadastro de equipamento</h2>
                </div>
                <?php if ($msg !== '') { ?><div class="info">
                        <p><b><?= e($msg) ?></b></p>
                    </div><?php } ?>
                <div class="grupo">Hostname:<p><input type="text" name="hostname" maxlength="100" value="<?= e($values['hostname']) ?>"></p>
                </div>
                <div class="grupo">Endereço IPv4:<p><input type="text" name="ipv4" maxlength="15" placeholder="192.168.10.21" value="<?= e($values['ipv4']) ?>" required></p>
                </div>
                <div class="grupo">Endereço MAC:<p><input type="text" name="mac" maxlength="17" placeholder="AA:BB:CC:DD:EE:FF" value="<?= e($values['mac']) ?>"></p>
                </div>
                <div class="grupo">Patrimônio:<p><input type="text" name="pat" maxlength="100" value="<?= e($values['pat']) ?>"></p>
                </div>
                <div class="grupo">Sistema operacional:<p><input type="text" name="so" maxlength="255" value="<?= e($values['so']) ?>"></p>
                </div>
                <div class="grupo">Atividade:<p><select name="atividade" required>
                            <?php foreach (['Em uso', 'Disponível', 'Descarte', 'Manutenção'] as $status) { ?>
                                <option value="<?= e($status) ?>" <?= $values['atividade'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php } ?>
                        </select></p>
                </div>
                <div class="grupo">Local:<p><input type="text" name="local" maxlength="100" value="<?= e($values['local']) ?>"></p>
                </div>
                <div class="grupo">Marca:<p><input type="text" name="marca" maxlength="100" value="<?= e($values['marca']) ?>"></p>
                </div>
                <div class="grupo">Tipo de equipamento:<p><select name="tipo_equipamento" required>
                            <option value="Outros">Outros</option>
                            <option value="Computador">Computador</option>
                            <option value="Notebook">Notebook</option>
                            <option value="Celular">Celular</option>
                            <option value="Impressora">Impressora</option>
                            <option value="Câmera">Câmera</option>
                            <option value="Gateway">Gateway</option>
                            <option value="Servidor">Servidor</option>
                            <option value="SmartWatch">SmartWatch</option>
                            <option value="Tablet">Tablet</option>
                        </select></p>
                </div>
                <button class="btn-salvar" type="submit">Cadastrar equipamento</button><br><br>
                <a id="voltar" href="listar_maquinas.php">← Voltar para equipamentos</a>
            </div>
        </div>
    </form>
</body>

</html>