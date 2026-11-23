<?php
declare(strict_types=1);

$appConfig = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
$dbConfig = $appConfig['db'];

mysqli_report(MYSQLI_REPORT_OFF);
$conexao = mysqli_connect(
    (string) $dbConfig['host'],
    (string) $dbConfig['user'],
    (string) $dbConfig['pass'],
    (string) $dbConfig['name'],
    (int) $dbConfig['port']
);

if (!$conexao) {
    error_log('MNET - erro de conexão MySQL: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Verifique config.local.php.');
}

mysqli_set_charset($conexao, 'utf8mb4');
