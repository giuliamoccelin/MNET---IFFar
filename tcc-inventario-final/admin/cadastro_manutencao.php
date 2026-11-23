<?php
include __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/security.php';
$msg = "";
require_admin($conexao);

$idEquipamento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id_equipamento', FILTER_VALIDATE_INT);
$dados_equip = null;
if ($idEquipamento) {
    $stmtEquip = mysqli_prepare($conexao, 'SELECT id, hostname, mac FROM equipamento WHERE id = ?');
    mysqli_stmt_bind_param($stmtEquip, 'i', $idEquipamento);
    mysqli_stmt_execute($stmtEquip);
    $dados_equip = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtEquip));
    mysqli_stmt_close($stmtEquip);
}
if (!$dados_equip) {
    http_response_code(404);
    exit('Equipamento não encontrado.');
}

if ($_POST) {
    require_csrf();
    $tipo_manutencao = (string) ($_POST['tipo_manutencao'] ?? '');
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    $custo = filter_input(INPUT_POST, 'custo', FILTER_VALIDATE_FLOAT);
    $allowedTypes = ['Preventiva', 'Software', 'Hardware', 'Rede'];
    $user = current_user($conexao);

    if (!in_array($tipo_manutencao, $allowedTypes, true) || $descricao === '' || $custo === false || $custo < 0) {
        $msg = 'Preencha os dados da manutenção corretamente.';
    } else {
        $equipmentId = (int) $dados_equip['id'];
        $userId = (int) $user['id'];
        $stmt = mysqli_prepare($conexao, 'INSERT INTO manutencao (id_equipamento, id_usuario, tipo_manutencao, descricao, custo) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissd', $equipmentId, $userId, $tipo_manutencao, $descricao, $custo);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: listar_manutencao.php?msg=' . rawurlencode('Manutenção cadastrada com sucesso.'));
            exit();
        }
        error_log('MNET - falha ao cadastrar manutenção: ' . mysqli_stmt_error($stmt));
        $msg = 'Falha ao cadastrar no banco de dados.';
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php include __DIR__ . '/../includes/head.php'; // Inclui o head de navegação 
    ?>
</head>

<body>
    <?php include __DIR__ . '/../includes/menu.php'; ?>

    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="id_equipamento" value="<?php echo (int) $dados_equip['id']; ?>">
        <div class="fundo-perfil">
            <div class="card-perfil">
                <div class="titulo">
                    <h2>Cadastro de Manutenção - Manual</h2>
                </div>

                <div class="info">
                    <p><b><?php echo $msg; ?></b></p>
                </div>
                <div class="grupo">
                    Equipamento: <br>
                    <p><input type="text" name="hostname" value="<?php echo e($dados_equip['hostname']); ?>" readonly></p>
                </div>
                <div class="grupo">
                    Endereço MAC: <br>
                    <p><input type="text" name="mac" value="<?php echo e($dados_equip['mac']); ?>" readonly></p>
                </div>
                <div class="grupo">
                    Tipo de manutenção: <br>
                    <p><select name="tipo_manutencao" required>
                            <option value='Preventiva'>Preventiva</option>
                            <option value='Software'>Software</option>
                            <option value='Hardware'>Hardware</option>
                            <option value='Rede'>Rede</option>
                        </select></p>
                </div>
                <div class="grupo">
                    Descrição: <br>
                    <p><input type="text" name="descricao" placeholder="Ex.: Limpeza do computador" required></p>
                </div>
                <div class="grupo">
                    Custo: <br>
                    <p><input type="number" name="custo" placeholder="Ex.: 150.00" step="0.01" required></p>
                </div>
                <button class="btn-salvar" type="submit">Cadastrar Manutenção</button><br><br>
                <a id="voltar" href="listar_maquinas.php" name="Voltar a listar Equipamentos">← Voltar para Equipamentos</a>
            </div>
        </div>
    </form>
</body>

</html>
