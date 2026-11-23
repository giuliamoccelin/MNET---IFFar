<?php
$msg = "";
require __DIR__ . '/includes/security.php';

if (isset($_GET['msg'])) {
    $msg = (string) $_GET['msg'];
}

if ($_POST) {
    include __DIR__ . '/includes/connect.php';
    require_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    $stmt = mysqli_prepare($conexao, 'SELECT id, email, senha, cargo FROM usuario WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    //verifica se o e-mail existe
    if (mysqli_num_rows($resultado) > 0) {
        // armazena os dados do usuário em um array associativo
        $dados = mysqli_fetch_assoc($resultado);
        //print_r($dados);
        if (password_verify("$senha", $dados['senha'])) {
            session_regenerate_id(true);
            $_SESSION['email'] = $dados['email'];
            if ($dados['cargo'] == "A") {
                header('Location: ' . BASE_URL . '/admin/admin.php');
            } else {
                header('Location: ' . BASE_URL . '/usuario/home.php');
            }
            exit();
        } else {
            $msg = "E-mail ou senha incorretos!";
        }
    } else {
        $msg = "E-mail ou senha incorretos!";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php include __DIR__ . '/includes/head.php'; // Inclui o head de navegação 
    ?>
</head>

<body>
    <div class="MNET-logo">
        <style>
            .MNET-logo {
                display: flex;
                align-items: center;
                font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
                color: rgb(35, 62, 132);
                justify-content: center;
                padding: 20px;
            }
        </style>
        <img src="<?= BASE_URL ?>/assets/img/2MNET-logo.png" name="MNET Logo" width="110" height="100">
        <h1>ㅤMNET - IFFar </h1>
    </div>
    <hr>
    <form action="" method="post" required>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div class="fundo-perfil">
            <div class="card-perfil">
                <div class="titulo">
                    <h2>Login</h2>
                </div>
                <div class="info">
                    <p><b><?php echo e($msg); ?></b></p>
                </div>

                <div class="grupo">
                    E-mail: <br>
                    <p> <input type="email" name="email" required></p>
                </div>
                <div class="grupo">
                    Senha: <br>
                    <p> <input type="password" name="senha" placeholder="senha" required></p>
                </div>
                <button class="btn-salvar" type="submit">Entrar</button><br>

            </div>

        </div>

    </form>

</body>

</html>
