<?php
declare(strict_types=1);

date_default_timezone_set('America/Argentina/Buenos_Aires');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/config.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['user'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
        $pdo->exec("SET time_zone = '-03:00'");
    }
    return $pdo;
}

function startSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('agua_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function validCsrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function audit(PDO $pdo, int $id, string $action, string $description): void
{
    $query = $pdo->prepare('INSERT INTO `log` (id_usuario, modulo, accion, registro_afectado, descripcion) VALUES (?, ?, ?, ?, ?)');
    $query->execute([$id, 'Usuarios', $action, 'usuarios:' . $id, $description]);
}

function currentAdmin(): ?array
{
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    $query = db()->prepare("SELECT id_usuario, nombre, apellido, usuario FROM usuarios WHERE id_usuario = ? AND estado = 'Activo'");
    $query->execute([$_SESSION['admin_id']]);
    $user = $query->fetch();
    if (!$user) {
        unset($_SESSION['admin_id']);
        return null;
    }
    return $user;
}
