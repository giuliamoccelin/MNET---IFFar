<?php require_once __DIR__ . '/caminhos.php'; ?>
<?php $dadosMenu = current_user($conexao); ?>
<div class="topbar">
    <div class="MNET-logo">
        <img src="<?= BASE_URL ?>/assets/img/2MNET-logo.png" name="MNET Logo" width="110" height="100">
        <h1>ㅤMNET - IFFar</h1>
    </div>

    <div class="nav-links">
        <nav>
            <ul id="menu">
                <li>
                    <a href="<?= BASE_URL ?>/usuario/home.php">HOME</a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/usuario/usuario_listar_maquinas.php">EQUIPAMENTOS</a>
                </li>
                <!--<li>
                    <a href="usuario_listar_usuarios.php">USUÁRIOS</a>
                </li>-->
                <li>
                    <a href="<?= BASE_URL ?>/usuario/usuario_listar_manutencao.php">MANUTENÇÕES</a>
                </li>
                <li>
                    <a href="#"><img src="<?= BASE_URL ?>/assets/img/perfil.png" alt="Perfil" width="16" height="16">ㅤPERFIL</a>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/usuario/usuario_perfil.php">VER PERFIL</a></li>
                        <li><a href="<?= BASE_URL ?>/comum/logout.php">SAIR DA CONTA</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</div>

<hr>

<script>
    const menuToggle = document.getElementById("menu-toggle");
    if (menuToggle) {
        menuToggle.addEventListener("click", function() {
            const menu = document.getElementById("menu");
            if (menu) menu.classList.toggle("open");
        });
    }
</script>
