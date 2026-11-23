<?php
ob_start();
include __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/security.php';
$msg = "";
require_admin($conexao);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Identificador inválido.');
}
$stmtSelect = mysqli_prepare($conexao, 'SELECT id, nome, cpf, email, cargo FROM usuario WHERE id = ?');
mysqli_stmt_bind_param($stmtSelect, 'i', $id);
mysqli_stmt_execute($stmtSelect);
$dados = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSelect));
if (!$dados) {
    http_response_code(404);
    exit('Usuário não encontrado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $cpfDigits = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));
    $novo_nome = trim((string) ($_POST['nome'] ?? ''));
    $novo_email = trim((string) ($_POST['email'] ?? ''));
    $novo_cargo = (string) ($_POST['cargo'] ?? '');
    $nova_senha = (string) ($_POST['senha'] ?? '');

    if (strlen($cpfDigits) !== 11 || filter_var($novo_email, FILTER_VALIDATE_EMAIL) === false || !in_array($novo_cargo, ['A', 'U'], true)) {
        $msg = 'Revise CPF, e-mail e perfil.';
    } elseif ($nova_senha !== '' && strlen($nova_senha) < 8) {
        $msg = 'A nova senha deve ter pelo menos 8 caracteres.';
    } else {
        $cpfFormatado = substr($cpfDigits, 0, 3) . '.' . substr($cpfDigits, 3, 3) . '.' . substr($cpfDigits, 6, 3) . '-' . substr($cpfDigits, 9, 2);
        if ($nova_senha !== '') {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmtUpdate = mysqli_prepare($conexao, 'UPDATE usuario SET nome = ?, email = ?, cargo = ?, cpf = ?, senha = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmtUpdate, 'sssssi', $novo_nome, $novo_email, $novo_cargo, $cpfFormatado, $hash, $id);
        } else {
            $stmtUpdate = mysqli_prepare($conexao, 'UPDATE usuario SET nome = ?, email = ?, cargo = ?, cpf = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmtUpdate, 'ssssi', $novo_nome, $novo_email, $novo_cargo, $cpfFormatado, $id);
        }
        if (mysqli_stmt_execute($stmtUpdate)) {
            header('Location: listar_usuarios.php?msg=' . rawurlencode('Usuário atualizado com sucesso.'));
            exit();
        }
        error_log('MNET - falha ao atualizar usuário: ' . mysqli_stmt_error($stmtUpdate));
        $msg = 'Não foi possível atualizar o usuário.';
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
    <div class="fundo-perfil">
        <div class="card-perfil">
            <div class="titulo">
                <h2>Configurações de Perfil - <?php echo e($dados['nome']); ?></h2>
            </div>
            <div class="info">
                <?php echo "<p><b>$msg</b></p>"; ?>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <div class="grupo">
                    <label>Nome Completo:</label>
                    <p><input type="text" name="nome" value="<?php echo e($dados['nome']); ?>"> </p>
                </div>

                <div class="grupo">
                    <label>CPF:</label>
                    <p> <input type="text" name="cpf" value="<?php echo e($dados['cpf']); ?>"> </p>
                </div>
                <div class="grupo">
                    <label>Novo E-mail de Contato:</label>
                    <p><input type="email" name="email" value="<?php echo e($dados['email']); ?>"> </p>
                </div>
                <div class="grupo">
                    Cargo: <br>
                    <p><select name="cargo" required>
                            <option value="A">Administrador</option>
                            <option value="U">Usuário Comum</option>
                        </select></p>
                </div>
                <div class="grupo">
                    Nova senha: <br>
                    <p><input type="password" name="senha"></p>
                </div>
                <button  class="btn-salvar">Salvar Alterações</button><br><br>
                <a id="voltar" href="listar_usuarios.php" name="Voltar a listar usuários">← Voltar para Usuários</a>
            </form>
        </div>
    </div>


</body>

</html>
