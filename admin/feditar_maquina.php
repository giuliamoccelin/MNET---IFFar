<?php
include __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/security.php';
$msg = "";
require_admin($conexao);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Identificador inválido.');
}
// 1. Busca os dados atuais da máquina usando o id do GET
$stmtSelect = mysqli_prepare($conexao, 'SELECT * FROM equipamento WHERE id = ?');
mysqli_stmt_bind_param($stmtSelect, 'i', $id);
mysqli_stmt_execute($stmtSelect);
$resultado = mysqli_stmt_get_result($stmtSelect);
$dados = mysqli_fetch_assoc($resultado);
if (!$dados) {
    http_response_code(404);
    exit('Equipamento não encontrado.');
}

// 2. Processa a atualização quando o formulário é enviado
if ($_POST) {
    require_csrf();
// mysqli_real_escape_string serve para evitar SQL Injection, que é uma técnica de ataque onde o invasor
// insere código SQL malicioso em campos de entrada para manipular o banco de dados. 
    $hostname = mysqli_real_escape_string($conexao, $_POST['hostname']);
    $ipv4 = mysqli_real_escape_string($conexao, $_POST['ipv4']);
    $mac = mysqli_real_escape_string($conexao, $_POST['mac']);
    $patrimonio = mysqli_real_escape_string($conexao, $_POST['patrimonio']);
    $local = mysqli_real_escape_string($conexao, $_POST['local']);
    $sistema_operacional = mysqli_real_escape_string($conexao, $_POST['sistema_operacional']);
    $marca = mysqli_real_escape_string($conexao, $_POST['marca']);
    $tipo_equipamento = mysqli_real_escape_string($conexao, $_POST['tipo_equipamento']);
    $atividade = mysqli_real_escape_string($conexao, $_POST['atividade']);

    $stmtUpdate = mysqli_prepare($conexao, 'UPDATE equipamento SET hostname = ?, ipv4 = ?, mac = ?, patrimonio = ?, local = ?, sistema_operacional = ?, atividade = ?, marca = ?, tipo_equipamento = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmtUpdate, 'sssssssssi', $hostname, $ipv4, $mac, $patrimonio, $local, $sistema_operacional, $atividade, $marca, $tipo_equipamento, $id);

    if (mysqli_stmt_execute($stmtUpdate)) {
        $msg = "Equipamento atualizado com sucesso!";

        $dados['hostname'] = $hostname;
        $dados['ipv4'] = $ipv4;
        $dados['mac'] = $mac;
        $dados['patrimonio'] = $patrimonio;
        $dados['local'] = $local;
        $dados['sistema_operacional'] = $sistema_operacional;
        $dados['marca'] = $marca;
        $dados['tipo_equipamento'] = $tipo_equipamento;
        $dados['atividade'] = $atividade;
    } else {
        $msg = "Erro técnico ao atualizar banco de dados.";
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
    <?php include __DIR__ . '/../includes/menu.php'; // Inclui o menu de navegação 
    ?>
    <form action="" method="post" required>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div class="fundo-perfil">
            <div class="card-perfil">
                <div class="titulo">
                    <h2>Configurações de equipamento - <?php echo e($dados['hostname']); ?></h2>
                </div>
                <div class="info">
                    <?php echo "<p><b>$msg</b></p>"; ?>
                </div>
                <div class="grupo">
                    Hostname: <br>
                    <p><input type="text" name="hostname" value="<?php echo e($dados['hostname']); ?>"></p>
                </div>
                <div class=" grupo">
                    Endereço IPV4: <br>
                    <p><input type="text" name="ipv4" value="<?php echo e($dados['ipv4']); ?>"></p>
                </div>
                <div class="grupo">
                    Endereço MAC: <br>
                    <p><input type="text" name="mac" value="<?php echo e($dados['mac']); ?>"></p>
                </div>
                <div class="grupo">
                    Patrimonio: <br>
                    <p><input type="text" name="patrimonio" value="<?php echo e($dados['patrimonio']); ?>"></p>
                </div>
                <div class="grupo">
                    Sistema Operacional: <br>
                    <p><input type="text" name="sistema_operacional" value="<?php echo htmlspecialchars($dados['sistema_operacional'], ENT_QUOTES, 'UTF-8'); ?>"> </p>
                </div>
                <div class="grupo">
                    Atividade: <br>
                    <p><select name="atividade" required>
                            <?php foreach (['Em uso', 'Disponível', 'Descarte', 'Manutenção'] as $atividade) { ?>
                                <option value="<?php echo htmlspecialchars($atividade, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $dados['atividade'] === $atividade ? 'selected' : ''; ?>><?php echo htmlspecialchars($atividade, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select></p>

                </div>
                <div class="grupo">
                    Local: <br>
                    <p><input type="text" name="local" value="<?php echo e($dados['local']); ?>"></p>
                </div>
                <div class="grupo">
                    Marca: <br>
                    <p><input type="text" name="marca" value="<?php echo e($dados['marca']); ?>"></p>
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
                <button class="btn-salvar" type="submit">Editar Máquina</button><br><br>
                <a id="voltar" href="listar_maquinas.php" name="Voltar a listar equipamentos">← Voltar para Equipamentos</a>

            </div>

        </div>

    </form>

</body>

</html>
