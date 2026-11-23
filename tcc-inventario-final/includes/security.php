<?php
declare(strict_types=1);

require_once __DIR__ . '/caminhos.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Requisição inválida. Atualize a página e tente novamente.');
    }
}

function current_user(mysqli $conexao): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    $email = $_SESSION['email'] ?? null;
    if (!is_string($email) || $email === '') {
        return null;
    }
    $stmt = mysqli_prepare($conexao, 'SELECT id, email, nome, cargo FROM usuario WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);
    return $user;
}

function require_login(mysqli $conexao): array
{
    $user = current_user($conexao);
    if (!$user) {
        header('Location: ' . BASE_URL . '/index.php?msg=' . rawurlencode('Acesso negado.'));
        exit();
    }
    return $user;
}

function require_admin(mysqli $conexao): array
{
    $user = require_login($conexao);
    if (($user['cargo'] ?? '') !== 'A') {
        http_response_code(403);
        exit('Acesso permitido somente a administradores.');
    }
    return $user;
}
