<?php

declare(strict_types=1);

require __DIR__ . '/../includes/connect.php';
require __DIR__ . '/../includes/security.php';
$usuarioAtual = require_admin($conexao);


//  CONFIGURAÇÃO DA PAGINAÇÃO 

// quantidade máxima de linhas exibidas por página
$por_pagina = 10;

// verifica se a URL veio com o parâmetro "pagina" (ex: listar_usuarios.php?pagina=2)
if (isset($_GET['pagina']) and is_numeric($_GET['pagina'])) {
    $pagina_atual = (int) $_GET['pagina'];
} else {
    $pagina_atual = 1; // se não veio nada na URL, começa na página 1
}

// garante que a página não seja menor que 1 (evita erro com números negativos ou zero)
if ($pagina_atual < 1) {
    $pagina_atual = 1;
}

// conta quantos usuários existem no total, para saber quantas páginas serão necessárias
$sql_total = "SELECT COUNT(*) AS total FROM equipamento";
$resultado_total = mysqli_query($conexao, $sql_total);
$linha_total = mysqli_fetch_assoc($resultado_total);
$total_registros = $linha_total['total'];

// calcula quantas páginas existem no total (arredondando para cima)
if ($total_registros > 0) {
    //a função ceil() arredonda para cima, garantindo que mesmo que haja um número "sobrando" de registros, uma nova página seja criada para eles
    $total_paginas = ceil($total_registros / $por_pagina);
} else {
    $total_paginas = 1;
}

// se o usuário tentar acessar uma página maior do que existe, ajusta para a última página válida
if ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}

// calcula a partir de qual registro a busca deve começar (o "offset" do SQL)
$inicio = ($pagina_atual - 1) * $por_pagina;
final class NmapScanner
{
    public function __construct(private string $nmapPath)
    {
        if (!is_file($this->nmapPath)) {
            throw new RuntimeException('Executável do Nmap não encontrado. Ajuste config.local.php.');
        }
        if (!function_exists('proc_open')) {
            throw new RuntimeException('A função proc_open está desabilitada no PHP.');
        }
        if (!function_exists('simplexml_load_string')) {
            throw new RuntimeException('A extensão SimpleXML não está habilitada no PHP.');
        }
    }

    public function discover(array $targets, int $timeout): array
    {
        $arguments = [
            '-sn',
            '-n',
            '-T4',
            '--max-retries',
            '1',
            '--host-timeout',
            '5s',
            '-oX',
            '-',
        ];
        array_push($arguments, ...$targets);
        $xml = $this->run($arguments, $timeout);
        return $this->parse($xml);
    }

    public function inspect(array $ips, string $profile, int $timeout): array
    {
        if ($ips === [] || $profile === 'rapido') {
            return [];
        }

        $args = ['-O', '-F', '--osscan-limit', '--max-os-tries', '1', '-T4'];
        if ($profile === 'detalhado') {
            array_push($args, '-sV', '--version-light', '--host-timeout', '20s');
        } else {
            array_push($args, '--host-timeout', '12s');
        }
        array_push($args, '-oX', '-');
        array_push($args, ...$ips);

        return $this->parse($this->run($args, $timeout));
    }

    private function run(array $arguments, int $timeout): string
    {
        $command = array_merge([$this->nmapPath], $arguments);
        $stdoutFile = tempnam(sys_get_temp_dir(), 'mnet_nmap_out_');
        $stderrFile = tempnam(sys_get_temp_dir(), 'mnet_nmap_err_');
        if ($stdoutFile === false || $stderrFile === false) {
            throw new RuntimeException('Não foi possível criar os arquivos temporários do scanner.');
        }
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $stdoutFile, 'w'],
            2 => ['file', $stderrFile, 'w'],
        ];
        $pipes = [];
        $process = proc_open($command, $descriptorSpec, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            @unlink($stdoutFile);
            @unlink($stderrFile);
            throw new RuntimeException('Não foi possível iniciar o Nmap.');
        }

        fclose($pipes[0]);
        $start = microtime(true);
        $timedOut = false;

        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if ((microtime(true) - $start) >= $timeout) {
                $timedOut = true;
                proc_terminate($process);
                break;
            }
            usleep(50000);
        } while (true);

        $exitCode = proc_close($process);
        $stdout = (string) @file_get_contents($stdoutFile);
        $stderr = (string) @file_get_contents($stderrFile);
        @unlink($stdoutFile);
        @unlink($stderrFile);

        if ($timedOut) {
            throw new RuntimeException("A varredura excedeu {$timeout} segundos e foi interrompida. Reduza a rede ou use o perfil rápido.");
        }
        if ($exitCode !== 0 && trim($stdout) === '') {
            $detail = trim($stderr);
            error_log('MNET - erro do Nmap: ' . $detail);
            throw new RuntimeException('O Nmap terminou com erro. Consulte o log do servidor e confirme as permissões do serviço Apache.');
        }
        if (trim($stdout) === '') {
            throw new RuntimeException('O Nmap não retornou XML.');
        }
        return $stdout;
    }

    private function parse(string $xmlText): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlText, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if ($xml === false) {
            throw new RuntimeException('Não foi possível interpretar o XML retornado pelo Nmap.');
        }

        $hosts = [];
        foreach ($xml->host as $host) {
            if ((string) $host->status['state'] !== 'up') {
                continue;
            }
            $item = [
                'ipv4' => '',
                'mac' => '',
                'marca' => '',
                'hostname' => '',
                'sistema_operacional' => '',
                'precisao_so' => 0,
                'latencia_ms' => null,
                'portas' => [],
            ];
            foreach ($host->address as $address) {
                $type = (string) $address['addrtype'];
                if ($type === 'ipv4') {
                    $item['ipv4'] = (string) $address['addr'];
                } elseif ($type === 'mac') {
                    $item['mac'] = strtoupper((string) $address['addr']);
                    $item['marca'] = (string) ($address['vendor'] ?? '');
                }
            }
            if (isset($host->hostnames->hostname[0])) {
                $item['hostname'] = (string) $host->hostnames->hostname[0]['name'];
            }
            if (isset($host->times['srtt'])) {
                $item['latencia_ms'] = round(((int) $host->times['srtt']) / 1000, 2);
            }
            foreach ($host->os->osmatch ?? [] as $osMatch) {
                $accuracy = (int) $osMatch['accuracy'];
                if ($accuracy > $item['precisao_so']) {
                    $item['precisao_so'] = $accuracy;
                    $item['sistema_operacional'] = (string) $osMatch['name'];
                }
            }
            foreach ($host->ports->port ?? [] as $port) {
                if ((string) $port->state['state'] !== 'open') {
                    continue;
                }
                $service = $port->service;
                $description = trim(implode(' ', array_filter([
                    (string) ($service['name'] ?? ''),
                    (string) ($service['product'] ?? ''),
                    (string) ($service['version'] ?? ''),
                ])));
                $item['portas'][] = [
                    'porta' => (int) $port['portid'],
                    'protocolo' => (string) $port['protocol'],
                    'servico' => $description,
                ];
            }
            if ($item['ipv4'] !== '') {
                $hosts[$item['ipv4']] = $item;
            }
        }
        return $hosts;
    }
}

function locateNmap(array $scanConfig): string
{
    $configured = trim((string) ($scanConfig['nmap_path'] ?? ''));
    $candidates = array_filter([
        $configured,
        getenv('ProgramFiles(x86)') ? getenv('ProgramFiles(x86)') . '\\Nmap\\nmap.exe' : null,
        getenv('ProgramFiles') ? getenv('ProgramFiles') . '\\Nmap\\nmap.exe' : null,
        'C:\\Program Files (x86)\\Nmap\\nmap.exe',
        'C:\\Program Files\\Nmap\\nmap.exe',
        '/usr/bin/nmap',
        '/usr/local/bin/nmap',
    ]);
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return $configured;
}

function ipv4Number(string $ip): int
{
    $packed = @inet_pton($ip);
    if ($packed === false || strlen($packed) !== 4) {
        throw new InvalidArgumentException('Endereço IPv4 inválido.');
    }
    return (int) unpack('N', $packed)[1];
}

function cidrInfo(string $cidr): array
{
    if (!preg_match('/^([^\/]+)\/(\d{1,2})$/', trim($cidr), $match)) {
        throw new InvalidArgumentException('Informe a rede no formato IPv4/CIDR, por exemplo 192.168.1.0/24.');
    }
    $ip = $match[1];
    $prefix = (int) $match[2];
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || $prefix < 0 || $prefix > 32) {
        throw new InvalidArgumentException('Rede IPv4/CIDR inválida.');
    }
    $number = ipv4Number($ip);
    $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
    $network = $number & $mask;
    $count = 2 ** (32 - $prefix);
    return [long2ip($network), $prefix, $count, $network, $network + $count - 1];
}

function cidrContains(string $container, string $candidate): bool
{
    [,,, $containerStart, $containerEnd] = cidrInfo($container);
    [,,, $candidateStart, $candidateEnd] = cidrInfo($candidate);
    return $candidateStart >= $containerStart && $candidateEnd <= $containerEnd;
}

function ipv4Range(string $initialIp, string $finalIp, int $maximumAddresses): array
{
    if (
        filter_var($initialIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
        || filter_var($finalIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
    ) {
        throw new InvalidArgumentException('Informe dois endereços IPv4 válidos para o intervalo.');
    }
    $start = ipv4Number($initialIp);
    $end = ipv4Number($finalIp);
    if ($start > $end) {
        throw new InvalidArgumentException('O IP inicial não pode ser maior que o IP final.');
    }
    $count = $end - $start + 1;
    if ($count > $maximumAddresses) {
        throw new InvalidArgumentException("O intervalo contém {$count} endereços; o limite é {$maximumAddresses}.");
    }
    return [$start, $end, $count];
}

function usableLocalIpv4(string $candidate): bool
{
    return filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
        && !str_starts_with($candidate, '127.')
        && !str_starts_with($candidate, '169.254.')
        && !str_starts_with($candidate, '0.');
}

function connectedIpv4(): ?string
{
    $candidates = [];

    // Uma conexão UDP sem envio escolhe a interface da rota padrão do servidor.
    if (function_exists('stream_socket_client')) {
        $socket = @stream_socket_client('udp://192.0.2.1:9', $errorNumber, $errorText, 1);
        if (is_resource($socket)) {
            $localAddress = @stream_socket_get_name($socket, false);
            fclose($socket);
            if (is_string($localAddress)) {
                $candidates[] = explode(':', $localAddress, 2)[0];
            }
        }
    }

    $candidates[] = (string) ($_SERVER['SERVER_ADDR'] ?? '');
    foreach ($candidates as $candidate) {
        if (usableLocalIpv4($candidate)) {
            return $candidate;
        }
    }

    $hostname = gethostname();
    if (is_string($hostname) && $hostname !== '') {
        $resolved = gethostbyname($hostname);
        if (usableLocalIpv4($resolved)) {
            return $resolved;
        }
    }
    return null;
}

function automaticCidr(int $addressCount, ?string $sourceIp = null): string
{
    $allowedCounts = [1, 2, 4, 8, 16, 32, 64, 128, 256];
    if (!in_array($addressCount, $allowedCounts, true)) {
        throw new InvalidArgumentException('Quantidade de endereços inválida.');
    }
    $ip = $sourceIp ?? connectedIpv4();
    if ($ip === null) {
        throw new RuntimeException('Não foi possível detectar um IPv4 utilizável no servidor. Use o modo CIDR manual.');
    }
    $prefix = 32 - (int) round(log($addressCount, 2));
    [$network] = cidrInfo($ip . '/' . $prefix);
    return $network . '/' . $prefix;
}

function registeredEquipment(mysqli $conexao): array
{
    $result = mysqli_query($conexao, 'SELECT id, hostname, ipv4, mac FROM equipamento');
    $byIp = [];
    $byMac = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ip = trim((string) $row['ipv4']);
        $mac = strtoupper(preg_replace('/[^0-9A-F]/i', '', (string) $row['mac']));
        if ($ip !== '') {
            $byIp[$ip] = $row;
        }
        if (strlen($mac) === 12) {
            $byMac[$mac] = $row;
        }
    }
    return [$byIp, $byMac];
}

function markRegistered(array $hosts, array $byIp, array $byMac): array
{
    foreach ($hosts as &$host) {
        $normalizedMac = strtoupper(preg_replace('/[^0-9A-F]/i', '', $host['mac']));
        $record = strlen($normalizedMac) === 12 ? ($byMac[$normalizedMac] ?? null) : null;
        $record ??= $byIp[$host['ipv4']] ?? null;
        $host['cadastro'] = $record;
    }
    unset($host);
    return $hosts;
}

function displayValue(mixed $value): string
{
    return trim((string) $value) === '' ? 'Não identificado' : (string) $value;
}

$scanConfig = $appConfig['scan'];
$message = '';
$messageType = 'info';
$hosts = [];
$searchedNetwork = '';
$mode = $_POST['modo'] ?? 'manual';
$manualCidr = trim((string) ($_POST['subnet'] ?? '192.168.1.0/24'));
$initialIp = trim((string) ($_POST['ip_inicial'] ?? ''));
$finalIp = trim((string) ($_POST['ip_final'] ?? ''));
$addressCount = (int) ($_POST['quantidade_hosts'] ?? 256);
$automaticIp = connectedIpv4();
$automaticPreview = '';
if ($automaticIp !== null && in_array($addressCount, [1, 2, 4, 8, 16, 32, 64, 128, 256], true)) {
    $automaticPreview = automaticCidr($addressCount, $automaticIp);
}
$profile = $_POST['perfil'] ?? 'rapido';
$onlyNew = !isset($_POST['mostrar_cadastrados']);
$minimumPrefix = (int) ($scanConfig['minimum_prefix'] ?? 24);
$maximumAddresses = 2 ** (32 - max(0, min(32, $minimumPrefix)));
$maximumTimeout = max(25, min(60, (int) ($scanConfig['timeout_seconds'] ?? 45)));
$timeoutChoices = array_values(array_filter([25, 30, 45, 60], static fn(int $seconds): bool => $seconds <= $maximumTimeout));
$defaultTimeout = (int) ($scanConfig['default_timeout_seconds'] ?? 30);
if (!in_array($defaultTimeout, $timeoutChoices, true)) {
    $defaultTimeout = $timeoutChoices[0];
}
$selectedTimeout = (int) ($_POST['tempo_limite'] ?? $defaultTimeout);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        if (!in_array($mode, ['manual', 'automatica', 'intervalo'], true)) {
            throw new InvalidArgumentException('Modo de rede inválido.');
        }
        if (!in_array($profile, ['rapido', 'equilibrado', 'detalhado'], true)) {
            throw new InvalidArgumentException('Perfil de varredura inválido.');
        }
        if (!in_array($selectedTimeout, $timeoutChoices, true)) {
            throw new InvalidArgumentException('Tempo de varredura inválido ou acima do limite configurado.');
        }
        if ($minimumPrefix < 0 || $minimumPrefix > 32) {
            throw new RuntimeException('O limite minimum_prefix da configuração é inválido.');
        }
        if ($mode === 'intervalo') {
            [$start, $end, $totalAddresses] = ipv4Range($initialIp, $finalIp, $maximumAddresses);
            $searchedNetwork = $initialIp . ' até ' . $finalIp;
            $scanTargets = [];
            for ($address = $start; $address <= $end; $address++) {
                $scanTargets[] = long2ip($address);
            }
        } else {
            $searchedNetwork = $mode === 'automatica' ? automaticCidr($addressCount, $automaticIp) : $manualCidr;
            [, $prefix, $totalAddresses, $start, $end] = cidrInfo($searchedNetwork);
            if ($prefix < $minimumPrefix) {
                throw new InvalidArgumentException("A configuração permite no máximo /{$minimumPrefix}. Divida redes maiores em blocos menores.");
            }
            $scanTargets = [$searchedNetwork];
        }
        $allowed = false;
        foreach ((array) $scanConfig['allowed_networks'] as $allowedNetwork) {
            [,,, $allowedStart, $allowedEnd] = cidrInfo((string) $allowedNetwork);
            if ($start >= $allowedStart && $end <= $allowedEnd) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $detected = $mode === 'automatica' ? " IP detectado: {$automaticIp}; rede calculada: {$searchedNetwork}." : '';
            throw new InvalidArgumentException('A rede não pertence aos blocos autorizados em config.local.php.' . $detected);
        }

        $timeout = $selectedTimeout;
        @set_time_limit($timeout + 10);
        $lockPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mnet_scan_' . hash('sha256', dirname(__DIR__)) . '.lock';
        $scanLock = @fopen($lockPath, 'c');
        if ($scanLock === false) {
            throw new RuntimeException('Não foi possível criar o bloqueio da varredura. Confira a pasta temporária do PHP.');
        }
        if (!flock($scanLock, LOCK_EX | LOCK_NB)) {
            fclose($scanLock);
            throw new RuntimeException('Já existe uma varredura em andamento nesta instalação. Aguarde a conclusão.');
        }
        try {
            $start = microtime(true);
            $scanner = new NmapScanner(locateNmap($scanConfig));
            $hosts = $scanner->discover($scanTargets, $timeout);
            $discoveredCount = count($hosts);
            [$byIp, $byMac] = registeredEquipment($conexao);
            $hosts = markRegistered($hosts, $byIp, $byMac);

            $targets = array_values(array_map(
                static fn(array $host): string => $host['ipv4'],
                array_filter($hosts, static fn(array $host): bool => !$onlyNew || $host['cadastro'] === null)
            ));
            $detailLimit = $profile === 'detalhado'
                ? min(16, (int) $scanConfig['max_detailed_hosts'])
                : (int) $scanConfig['max_detailed_hosts'];
            $detailWarning = '';
            if ($profile !== 'rapido' && count($targets) > $detailLimit) {
                $detailWarning = "O detalhamento foi omitido porque há mais de {$detailLimit} hosts selecionados. Reduza a faixa para obter portas e serviços.";
                $targets = [];
            }
            $remaining = $timeout - (int) ceil(microtime(true) - $start);
            if ($profile !== 'rapido' && $targets !== [] && $remaining >= 3) {
                try {
                    $details = $scanner->inspect($targets, $profile, $remaining);
                    foreach ($details as $ip => $detail) {
                        if (!isset($hosts[$ip])) {
                            continue;
                        }
                        foreach ($detail as $key => $value) {
                            if ($value !== '' && $value !== [] && $value !== null && $value !== 0) {
                                $hosts[$ip][$key] = $value;
                            }
                        }
                    }
                } catch (RuntimeException $detailError) {
                    error_log('MNET - detalhamento parcial: ' . $detailError->getMessage());
                    $detailWarning = 'A descoberta terminou, mas o detalhamento não foi concluído. Reduza a faixa ou use o perfil rápido.';
                }
            } elseif ($profile !== 'rapido' && $targets !== [] && $detailWarning === '') {
                $detailWarning = 'A descoberta terminou, mas não restou tempo para detalhar os dispositivos.';
            }
            if ($onlyNew) {
                $hosts = array_filter($hosts, static fn(array $host): bool => $host['cadastro'] === null);
            }
            $message = sprintf(
                'Rede %s: %d endereço(s) possíveis, %d equipamento(s) encontrado(s), %d exibido(s), %.1f s decorridos (limite %d s).',
                $searchedNetwork,
                $totalAddresses,
                $discoveredCount,
                count($hosts),
                microtime(true) - $start,
                $timeout
            );
            if ($detailWarning !== '') {
                $message .= ' ' . $detailWarning;
            }
            if ($onlyNew && $discoveredCount > count($hosts)) {
                $message .= ' Equipamentos já cadastrados foram ocultados da lista.';
            }
            $messageType = 'success';
        } finally {
            flock($scanLock, LOCK_UN);
            fclose($scanLock);
        }
    } catch (Throwable $error) {
        error_log('MNET - falha no scanner: ' . $error->getMessage());
        $message = $error->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/../includes/menu.php'; ?>
    <div class="fundo-tabela">
        <div class="card-tabela">
            <div class="titulo">
                <h2>Escanear equipamentos</h2>
            </div>
            <form method="post" class="painel-filtros">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="filtros-titulo">Configuração da varredura</div>
                <div class="filtros-grid scan-grid">
                    <div class="filtro-grupo">
                        <label for="modo">Origem da rede</label>
                        <select id="modo" name="modo">
                            <option value="manual" <?= $mode === 'manual' ? 'selected' : '' ?>>CIDR informado manualmente</option>
                            <option value="intervalo" <?= $mode === 'intervalo' ? 'selected' : '' ?>>IP inicial e IP final</option>
                            <option value="automatica" <?= $mode === 'automatica' ? 'selected' : '' ?>>Rede conectada ao servidor</option>
                        </select>
                    </div>
                    <div class="filtro-grupo" id="grupo-subnet">
                        <label for="subnet">Rede IPv4 em CIDR</label>
                        <input type="text" id="subnet" name="subnet" value="<?= e($manualCidr) ?>" placeholder="192.168.1.0/24">
                        <small>/32 = 1 endereço, /28 = 16, /26 = 64, /24 = 256.</small>
                    </div>
                    <div class="filtro-grupo" id="grupo-hosts">
                        <label for="quantidade_hosts">Endereços a examinar</label>
                        <select id="quantidade_hosts" name="quantidade_hosts">
                            <?php foreach ([1, 2, 4, 8, 16, 32, 64, 128, 256] as $count) { ?>
                                <option value="<?= $count ?>" <?= $addressCount === $count ? 'selected' : '' ?>><?= $count ?> (<?= '/' . (32 - (int) round(log($count, 2))) ?>)</option>
                            <?php } ?>
                        </select>
                        <small>
                            <?php if ($automaticPreview !== '') { ?>
                                IP detectado no servidor: <?= e($automaticIp) ?>. Bloco a examinar: <?= e($automaticPreview) ?>.
                            <?php } else { ?>
                                Não foi possível detectar um IPv4 utilizável. Use CIDR manual.
                            <?php } ?>
                        </small>
                    </div>
                    <div class="filtro-grupo" id="grupo-ip-inicial">
                        <label for="ip_inicial">IP inicial</label>
                        <input type="text" id="ip_inicial" name="ip_inicial" value="<?= e($initialIp) ?>" placeholder="192.168.1.10" inputmode="decimal">
                    </div>
                    <div class="filtro-grupo" id="grupo-ip-final">
                        <label for="ip_final">IP final (incluído)</label>
                        <input type="text" id="ip_final" name="ip_final" value="<?= e($finalIp) ?>" placeholder="192.168.1.30" inputmode="decimal">
                        <small>Até <?= $maximumAddresses ?> endereços, dentro de uma rede autorizada.</small>
                    </div>
                    <div class="filtro-grupo">
                        <label for="perfil">Nível de detalhe</label>
                        <select id="perfil" name="perfil">
                            <option value="rapido" <?= $profile === 'rapido' ? 'selected' : '' ?>>Rápido: IP, MAC e fabricante</option>
                            <option value="equilibrado" <?= $profile === 'equilibrado' ? 'selected' : '' ?>>Equilibrado: inclui SO e portas comuns</option>
                            <option value="detalhado" <?= $profile === 'detalhado' ? 'selected' : '' ?>>Detalhado: inclui versões de serviços</option>
                        </select>
                        <small>O perfil Rápido não examina sistema operacional nem portas.</small>
                    </div>
                    <div class="filtro-grupo">
                        <label for="tempo_limite">Tempo máximo da varredura</label>
                        <select id="tempo_limite" name="tempo_limite">
                            <?php foreach ($timeoutChoices as $seconds) { ?>
                                <option value="<?= $seconds ?>" <?= $selectedTimeout === $seconds ? 'selected' : '' ?>><?= $seconds ?> segundos</option>
                            <?php } ?>
                        </select>
                        <small>O servidor permite até <?= $maximumTimeout ?> s. Redes maiores podem exigir tarefa em segundo plano.</small>
                    </div>
                    <div class="filtro-grupo checkbox-grupo">
                        <span class="filtro-legenda">Resultados</span>
                        <label><input type="checkbox" name="mostrar_cadastrados" value="1" <?= !$onlyNew ? 'checked' : '' ?>> Mostrar também equipamentos já cadastrados</label>
                        <small>Desmarcado: os cadastrados são reconhecidos por MAC e depois por IP.</small>
                    </div>
                </div>
                <div class="filtros-acoes"><button type="submit" class="btn-filtrar">Escanear rede</button></div>
            </form>

            <div id="scan-progresso" class="scan-progresso" role="status" aria-live="polite" hidden>
                <strong>Varredura em andamento.</strong> Os resultados aparecerão quando o Nmap terminar. A barra indica atividade, não uma porcentagem concluída.
                <progress aria-label="Varredura em andamento"></progress>
                <span id="scan-tempo-decorrido">Tempo decorrido: 0 s.</span>
            </div>

            <?php if ($message !== '') { ?>
                <p class="info-resultado mensagem-<?= e($messageType) ?>"><?= e($message) ?></p>
            <?php } ?>

            <?php if ($hosts !== []) { ?>
                <div class="tabela-responsiva">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Hostname</th>
                                <th>IPv4</th>
                                <th>MAC</th>
                                <th>Marca</th>
                                <th>SO</th>
                                <th>Latência</th>
                                <th>Portas e serviços</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hosts as $host) { ?>
                                <tr>
                                    <td><?= $host['cadastro'] ? 'Cadastrado' : 'Novo' ?></td>
                                    <td><?= e(displayValue($host['hostname'])) ?></td>
                                    <td><?= e($host['ipv4']) ?></td>
                                    <td><?= e(displayValue($host['mac'])) ?></td>
                                    <td><?= e(displayValue($host['marca'])) ?></td>
                                    <td><?= e(displayValue($host['sistema_operacional'])) ?><?= $host['precisao_so'] ? ' (' . (int) $host['precisao_so'] . '%)' : '' ?></td>
                                    <td><?= $host['latencia_ms'] !== null ? e($host['latencia_ms']) . ' ms' : 'N/D' ?></td>
                                    <td>
                                        <?php if ($host['portas'] === []) {
                                            echo 'Não examinadas';
                                        } else { ?>
                                            <details>
                                                <summary><?= count($host['portas']) ?> aberta(s)</summary>
                                                <ul><?php foreach ($host['portas'] as $port) { ?><li><?= e($port['porta'] . '/' . $port['protocolo'] . ' ' . $port['servico']) ?></li><?php } ?></ul>
                                            </details>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($host['cadastro']) { ?>
                                            <a class="btn-salvar" href="feditar_maquina.php?id=<?= (int) $host['cadastro']['id'] ?>">Ver cadastro</a>
                                        <?php } else { ?>
                                            <a class="btn-salvar" href="cadastro_maquina.php?hostname=<?= rawurlencode($host['hostname']) ?>&ipv4=<?= rawurlencode($host['ipv4']) ?>&mac=<?= rawurlencode($host['mac']) ?>&marca=<?= rawurlencode($host['marca']) ?>&so=<?= rawurlencode($host['sistema_operacional']) ?>">Cadastrar</a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            <!-- NAVEGAÇÃO DA PAGINAÇÃO -->
                            <div class="paginacao">
                                <?php
                                // botão "Anterior": só fica clicável se não estivermos na primeira página
                                if ($pagina_atual > 1) {
                                    $pagina_anterior = $pagina_atual - 1;
                                    echo "<a href='escanear_nmap.php?pagina=$pagina_anterior'>&laquo; Anterior</a>";
                                } else {
                                    // se estivermos na primeira página, o botão "Anterior" fica desabilitado (sem link)
                                    echo "<a class='desabilitado'>&laquo; Anterior</a>";
                                }

                                // gera um botão para cada página existente
                                for ($i = 1; $i <= $total_paginas; $i++) {
                                    if ($i == $pagina_atual) {
                                        // página atual recebe a classe "ativo" (já destacada no CSS)
                                        echo "<a href='escanear_nmap.php?pagina=$i' class='ativo'>$i</a>";
                                    } else {
                                        // páginas diferentes da atual ficam com link normal
                                        echo "<a href='escanear_nmap.php?pagina=$i'>$i</a>";
                                    }
                                }

                                // botão "Próxima": só fica clicável se não estivermos na última página
                                if ($pagina_atual < $total_paginas) {
                                    $proxima_pagina = $pagina_atual + 1;
                                    // se ainda houver páginas seguintes, o botão "Próxima" recebe um link para a próxima página
                                    echo "<a href='escanear_nmap.php?pagina=$proxima_pagina'>Próxima &raquo;</a>";
                                } else {
                                    // se estivermos na última página, o botão "Próxima" fica desabilitado (sem link)
                                    echo "<a class='desabilitado'>Próxima &raquo;</a>";
                                }
                                ?>
                            </div>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>
    </div>
    <script>
        function atualizarModoRede() {
            const modo = document.getElementById('modo').value;
            document.getElementById('grupo-subnet').hidden = modo !== 'manual';
            document.getElementById('grupo-hosts').hidden = modo !== 'automatica';
            document.getElementById('grupo-ip-inicial').hidden = modo !== 'intervalo';
            document.getElementById('grupo-ip-final').hidden = modo !== 'intervalo';
        }
        document.getElementById('modo').addEventListener('change', atualizarModoRede);
        atualizarModoRede();
        document.querySelector('.painel-filtros').addEventListener('submit', function(event) {
            event.preventDefault();
            const progress = document.getElementById('scan-progresso');
            const elapsed = document.getElementById('scan-tempo-decorrido');
            const limit = Number(document.getElementById('tempo_limite').value);
            const button = this.querySelector('button[type="submit"]');
            const started = Date.now();
            progress.hidden = false;
            button.disabled = true;
            button.textContent = 'Escaneando...';

            function updateElapsed() {
                elapsed.textContent = 'Tempo decorrido: ' + Math.floor((Date.now() - started) / 1000) +
                    ' s. Limite escolhido: ' + limit + ' s.';
            }
            updateElapsed();
            window.setInterval(updateElapsed, 1000);
            const form = this;
            window.setTimeout(function() {
                HTMLFormElement.prototype.submit.call(form);
            }, 50);
        });
    </script>
</body>

</html>