<?php
include __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/security.php';
$msg = "";
require_admin($conexao);

if ($_POST) {
    require_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $cpfDigits = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));
    $cargo = (string) ($_POST['cargo'] ?? '');

    if (strlen($cpfDigits) !== 11) {
        $msg = 'CPF deve conter 11 dígitos.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $msg = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 8) {
        $msg = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif (!in_array($cargo, ['A', 'U'], true)) {
        $msg = 'Perfil de usuário inválido.';
    } else {
        $cpfFormatado = substr($cpfDigits, 0, 3) . '.' . substr($cpfDigits, 3, 3) . '.' . substr($cpfDigits, 6, 3) . '-' . substr($cpfDigits, 9, 2);
        $check = mysqli_prepare($conexao, 'SELECT id FROM usuario WHERE cpf = ? OR email = ? LIMIT 1');
        mysqli_stmt_bind_param($check, 'ss', $cpfFormatado, $email);
        mysqli_stmt_execute($check);
        $duplicate = mysqli_stmt_get_result($check)->num_rows > 0;
        mysqli_stmt_close($check);
        if ($duplicate) {
            $msg = 'CPF ou e-mail já cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conexao, 'INSERT INTO usuario (email, senha, nome, cpf, cargo) VALUES (?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sssss', $email, $hash, $nome, $cpfFormatado, $cargo);
            $msg = mysqli_stmt_execute($stmt) ? 'Usuário cadastrado com sucesso.' : 'Falha ao cadastrar usuário.';
            if ($msg !== 'Usuário cadastrado com sucesso.') {
                error_log('MNET - falha ao cadastrar usuário: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }
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
    <form action="cadastro.php" method="post" required>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div class="fundo-perfil">
            <div class="card-perfil">
                <div class="titulo">
                    <h2>Cadastro de Usuário</h2>
                </div>
                <div class="info">
                    <p><b><?php echo e($msg); ?></b></p>
                </div>
                <div class="grupo">
                    Nome: <br>
                    <p><input type="text" name="nome" required></p>
                </div>
                <div class="grupo">
                    CPF: <br>
                    <div class="info-escritas">
                        <p><b>Não é necessário inserir pontos ou traço, apenas os números. O CPF será formatado automaticamente.</b></p>
                    </div>
                    <p><input type="text" placeholder="xxx.xxx.xxx-xx" name="cpf" required></p>
                </div>
                <div class="grupo">
                    E-mail: <br>
                    <p><input type="email" name="email" required></p>
                </div>
                <div class="grupo">
                    Cargo: <br>
                    <p><select name="cargo" required>
                            <option value="A">Administrador</option>
                            <option value="U">Usuário Comum</option>
                        </select></p>
                </div>
                <div class="grupo">
                    Senha: <br>
                    <!-- Colocar as informações que são necessárias para a criação da senha-->
                    <p><input type="password" name="senha" id="senha" required minlength="8" placeholder="Mínimo 8 caracteres"></p>
                </div>
                <button class="btn-salvar" type="submit">Cadastrar</button><br><br>
                <a id="voltar" href="listar_usuarios.php">← Voltar para usuários</a>

            </div>

        </div>
    </form>

</body>

</html>