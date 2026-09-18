<?php
declare(strict_types=1);
require __DIR__ . '/app.php';
startSession();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}
if (!validCsrf()) {
    http_response_code(403);
    exit('Formulario vencido. Volvé al inicio e intentá nuevamente.');
}
if (isset($_SESSION['admin_id'])) {
    try {
        audit(db(), (int) $_SESSION['admin_id'], 'Cierre de sesión', 'Cierre de sesión');
    } catch (PDOException $exception) {
        // Aunque la base no responda, el administrador debe poder salir.
        error_log('No se pudo registrar el cierre de sesión: ' . $exception->getMessage());
    }
}
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['notice'] = 'Sesión cerrada correctamente.';
header('Location: index.php');
exit;
