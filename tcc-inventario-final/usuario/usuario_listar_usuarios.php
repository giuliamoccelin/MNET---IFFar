<?php
include __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/security.php';
$msg = "";
require_admin($conexao);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php include __DIR__ . '/../includes/head.php'; // Inclui o head de navegação 
    ?>
</head>

<body>
    <?php include __DIR__ . '/../includes/menu_usuario.php'; // Inclui o menu de navegação 
    ?>
    <div class="fundo-tabela">
        <div class="card-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>E-mail</th>
                        <th>Cargo</th>
                        <th>Data de Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include __DIR__ . '/../includes/connect.php';
                    $sql = "SELECT id, nome, cpf, email, cargo, data_registro FROM usuario ORDER BY id";
                    $resultado = mysqli_query($conexao, $sql);

                    while ($dados = mysqli_fetch_assoc($resultado)) {

                        echo "<tr>";
                        echo "<td>" . e($dados['nome']) . "</td>";
                        echo "<td>" . e($dados['cpf']) . "</td>";
                        echo "<td>" . e($dados['email']) . "</td>";
                        echo "<td>";
                        if ($dados['cargo'] == 'A') {
                            echo "Administrador";
                        } else {
                            echo "Usuário Comum";
                        }
                        echo "</td>";
                        echo "<td>" . e($dados['data_registro']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
