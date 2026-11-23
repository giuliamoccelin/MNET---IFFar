<?php
declare(strict_types=1);

/*
 * Configuração padrão para desenvolvimento. Em cada computador, copie
 * config.local.example.php para config.local.php e ajuste apenas esse arquivo.
 * O config.local.php não deve ser publicado no repositório.
 */
$config = [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tccinventario_ti',
        'user' => 'root',
        'pass' => '',
    ],
    'scan' => [
        // Deixe vazio para procurar automaticamente em locais comuns.
        'nmap_path' => '',
        // /24 = 256 endereços. Diminua para /25, /26 etc. se necessário.
        'minimum_prefix' => 24,
        // Teto por varredura. Para 60, altere explicitamente no computador servidor.
        'timeout_seconds' => 45,
        'default_timeout_seconds' => 30,
        'max_detailed_hosts' => 32,
        // Blocos privados autorizados. Ajuste à política da instituição.
        'allowed_networks' => [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
        ],
    ],
];

$localConfig = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
if (is_file($localConfig)) {
    $local = require $localConfig;
    if (!is_array($local)) {
        throw new RuntimeException('config.local.php deve retornar um array.');
    }
    if (isset($local['scan']) && !is_array($local['scan'])) {
        throw new RuntimeException('scan em config.local.php deve ser um array.');
    }
    $config = array_replace_recursive($config, $local);
    // Redes autorizadas são uma lista completa, não itens a mesclar por posição.
    if (array_key_exists('allowed_networks', $local['scan'] ?? [])) {
        if (!is_array($local['scan']['allowed_networks'])) {
            throw new RuntimeException('scan.allowed_networks em config.local.php deve ser uma lista.');
        }
        $config['scan']['allowed_networks'] = array_values($local['scan']['allowed_networks']);
    }
}

return $config;
