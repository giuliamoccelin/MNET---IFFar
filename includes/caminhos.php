<?php
declare(strict_types=1);

/*
 * Define BASE_URL: o endereço da pasta raiz do projeto, como o navegador a enxerga.
 * Exemplos: "/tcc_inventario" (projeto em htdocs/tcc_inventario) ou "" (projeto direto em htdocs).
 *
 * Menu, cabeçalho e redirecionamentos são usados por páginas de pastas diferentes
 * (raiz, admin, usuario e comum). Um link como "listar_maquinas.php" funcionaria em
 * uma pasta e quebraria em outra, por isso os links compartilhados partem de BASE_URL.
 */
if (!defined('BASE_URL')) {
    $raizProjeto = realpath(dirname(__DIR__));
    $pastaScript = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    // SCRIPT_NAME é o caminho da página na URL e sempre usa "/", mesmo no Windows
    $urlPagina = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

    if ($pastaScript !== $raizProjeto) {
        // A página está em admin/, usuario/ ou comum/: sobe um nível para chegar à raiz
        $urlPagina = dirname($urlPagina);
    }

    // Codifica cada parte do caminho (espaços e símbolos) para uso seguro em links
    $partes = explode('/', $urlPagina);
    foreach ($partes as $indice => $parte) {
        $partes[$indice] = rawurlencode($parte);
    }
    define('BASE_URL', rtrim(implode('/', $partes), '/'));
}
