<?php
include __DIR__ . '/../includes/connect.php';
include __DIR__ . '/../includes/security.php';
$msg = "";
require_admin($conexao);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
require_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Identificador inválido.');
}
$stmt = mysqli_prepare($conexao, 'DELETE FROM equipamento WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
$ok = mysqli_stmt_execute($stmt);
$affected = $ok ? mysqli_stmt_affected_rows($stmt) : 0;
if (!$ok) {
    error_log('MNET - falha ao excluir equipamento: ' . mysqli_stmt_error($stmt));
}
$message = $affected > 0 ? 'Equipamento excluído.' : 'Não foi possível excluir. Verifique se há manutenções vinculadas.';
header('Location: listar_maquinas.php?msg=' . rawurlencode($message));
exit();
