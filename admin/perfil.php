<?php
include(__DIR__ . '/../includes/connect.php');
include(__DIR__ . '/../includes/security.php');
require_admin($conexao);
$email = $_SESSION['email'];
$msg = "";

// 1. Busca os dados atuais do usuário usando o email da sessão
$stmtSelect = mysqli_prepare($conexao, 'SELECT * FROM usuario WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($stmtSelect, 's', $email);
mysqli_stmt_execute($stmtSelect);
$resultado = mysqli_stmt_get_result($stmtSelect);
$dados = mysqli_fetch_assoc($resultado);

// 2. Processa a atualização quando o formulário é enviado
if (isset($_POST['atualizar'])) {
    require_csrf();
    // mysqli_real_escape_string serve para evitar SQL Injection, que é uma técnica de ataque onde o invasor
    // insere código SQL malicioso em campos de entrada para manipular o banco de dados. 
    $at_cpf = trim((string) ($_POST['cpf'] ?? ''));
    $novo_nome = trim((string) ($_POST['nome'] ?? ''));
    $novo_email = trim((string) ($_POST['email'] ?? ''));
    $nova_senha = (string) ($_POST['senha'] ?? '');
    $confirmar_senha = $_POST['senha_atual'] ?? '';

    // Validação de Segurança: Só altera se o "senha_antiga" digitada for igual à salva no banco
    if (password_verify($confirmar_senha, $dados['senha'])) {

        if (!empty($nova_senha)) {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmtUpdate = mysqli_prepare($conexao, 'UPDATE usuario SET cpf = ?, nome = ?, email = ?, senha = ? WHERE email = ?');
            mysqli_stmt_bind_param($stmtUpdate, 'sssss', $at_cpf, $novo_nome, $novo_email, $hash, $email);
        } else {
            $stmtUpdate = mysqli_prepare($conexao, 'UPDATE usuario SET cpf = ?, nome = ?, email = ? WHERE email = ?');
            mysqli_stmt_bind_param($stmtUpdate, 'ssss', $at_cpf, $novo_nome, $novo_email, $email);
        }

        if (mysqli_stmt_execute($stmtUpdate)) {
            $msg = "Perfil atualizado com sucesso!";
            $_SESSION['email'] = $novo_email;
            $email = $novo_email;
            // Atualiza as variáveis para refletir na tela imediatamente
            $dados['cpf'] = $at_cpf;
            $dados['nome'] = $novo_nome;
            $dados['email'] = $novo_email;
        } else {
            $msg = "Erro técnico ao atualizar banco de dados.";
        }
    } else {
        $msg = "Confirmação falhou: A senha antiga não confere";
    }
}

if (isset($_POST['excluir'])) {
    require_csrf();
    if ($email == 'admin@iffar.edu.br') {
        $msg = "Você não pode excluir a conta do administrador principal.";
    } else {
        $confirmar_senha = $_POST['senha_atual'] ?? '';

        if (password_verify($confirmar_senha, $dados['senha'])) {
            $stmtDelete = mysqli_prepare($conexao, 'DELETE FROM usuario WHERE email = ?');
            mysqli_stmt_bind_param($stmtDelete, 's', $email);
            if (mysqli_stmt_execute($stmtDelete)) {
                session_destroy();
                header('Location: ' . BASE_URL . '/index.php');
                exit();
            } else {
                $msg = "Erro técnico ao excluir conta.";
            }
        } else {
            $msg = "Confirmação falhou: A senha antiga não confere";
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
    <div class="fundo-perfil">
        <div class="card-perfil">
            <div class="titulo">
                <h2>Configurações de Perfil (Administrador)</h2>
            </div>
            <div class="info">
                <?php echo "<p><b>$msg</b></p>"; ?>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <div class="grupo">
                    <label>CPF:</label>
                    <p> <input type="text" name="cpf" value="<?php echo e($dados['cpf']); ?>"> </p>
                </div>
                <div class="grupo">
                    <label>Nome Completo:</label>
                    <p><input type="text" name="nome" value="<?php echo e($dados['nome']); ?>"> </p>
                </div>
                <div class="grupo">
                    Cargo: <br>
                    <p><input type="text" name="cargo" value="<?php if ($dados['cargo'] == 'A') {
                                                                    echo 'Administrador';
                                                                } else {
                                                                    echo 'Usuário Comum';
                                                                } ?>" readonly> </p>
                </div>
                <div class="grupo">
                    <label>E-mail de Contato:</label>
                    <p><input type="email" name="email" value="<?php echo e($email); ?>"> </p>
                </div>
                <strong>Confirmação de Segurança:</strong>
                <div class="info">
                    <p><b>Para autorizar as mudanças, você deve confirmar sua senha registrada anteriormente.</b></p>
                </div>
                <div class="grupo">
                    <label>Senha Atual:</label>
                    <p><input type="password" name="senha_atual" placeholder="Digite sua senha atual para confirmar" required> </p>
                </div>
                <div class="grupo">
                    <label>Nova Senha:</label>
                    <p><input type="password" name="senha" placeholder="Deixe vazio para manter"> </p>
                </div>
                <button type="submit" class="btn-salvar" name="atualizar">Salvar Alterações</button><br>
                <?php if ($email == 'admin@iffar.edu.br') {
                } else {
                    echo '<button type="submit" class="btn-excluir" name="excluir">Excluir Conta Permanentemente</button>';
                } ?>
            </form>
        </div>

</body>

</html>
