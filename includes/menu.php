<?php require_once __DIR__ . '/caminhos.php'; ?>
<?php

?>

<div class="topbar">
    <div class="MNET-logo">
        <img src="<?= BASE_URL ?>/assets/img/2MNET-logo.png" name="MNET Logo" width="110" height="105">
        <!--<img src="<?= BASE_URL ?>/assets/img/2MNET-logo.png" name="MNET Logo" width="110" height="100">-->
        <h1>ㅤMNET - IFFar</h1>
    </div>

    <nav>
        <ul id="menu">
            <li>
                <a href="<?= BASE_URL ?>/admin/admin.php">HOME</a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/escanear_nmap.php">ESCANEAR REDE</a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/listar_maquinas.php">EQUIPAMENTOS</a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/listar_usuarios.php">USUÁRIOS</a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/listar_manutencao.php">MANUTENÇÕES</a>
            </li>
            <li>
                <a href="#"><img src="<?= BASE_URL ?>/assets/img/perfil.png" alt="Perfil" width="16" height="16">ㅤPERFIL</a>
                <ul>
                    <li><a href="<?= BASE_URL ?>/admin/perfil.php">VER PERFIL</a></li>
                    <li><a href="<?= BASE_URL ?>/comum/logout.php">SAIR DA CONTA</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</div>

<hr>