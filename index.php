<?php
declare(strict_types=1);
require __DIR__ . '/app.php';
startSession();

$error = '';
$username = '';
try {
    if (currentAdmin()) {
        header('Location: inicio.php');
        exit;
    }
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $error = 'No se pudo conectar con la base de datos. Revisá la conexión e intentá nuevamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
    $password = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';
    if (!validCsrf()) {
        $error = 'La sesión del formulario venció. Recargá la página e intentá nuevamente.';
    } elseif (($_SESSION['blocked_until'] ?? 0) > time()) {
        $error = 'Hubo varios intentos incorrectos. Esperá un minuto antes de volver a intentar.';
    } elseif ($username === '' || $password === '' || strlen($username) > 320 || strlen($password) > 1024) {
        $error = 'Completá el usuario y la contraseña con datos válidos.';
    } else {
        $pdo = null;
        try {
            $pdo = db();
            $query = $pdo->prepare('SELECT id_usuario, contrasena_hash, estado FROM usuarios WHERE usuario = ? LIMIT 1');
            $query->execute([$username]);
            $user = $query->fetch();
            // El hash de relleno evita una respuesta inmediata para usuarios inexistentes.
            $validPassword = password_verify($password, $user['contrasena_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi');
            if (!$user || !$validPassword || $user['estado'] !== 'Activo') {
                $error = 'Usuario o contraseña incorrectos, o cuenta inactiva.';
                $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
                if ($_SESSION['attempts'] >= 5) {
                    $_SESSION['blocked_until'] = time() + 60;
                    $_SESSION['attempts'] = 0;
                }
            } else {
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?')->execute([$user['id_usuario']]);
                audit($pdo, (int) $user['id_usuario'], 'Acceso', 'Inicio de sesión');
                $pdo->commit();
                session_regenerate_id(true);
                $_SESSION = ['admin_id' => (int) $user['id_usuario'], 'csrf' => bin2hex(random_bytes(32))];
                header('Location: inicio.php');
                exit;
            }
        } catch (PDOException $exception) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($exception->getMessage());
            $error = 'No se pudo completar el ingreso. Revisá la conexión a la base e intentá nuevamente.';
        }
    }
}
$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | AGUAS PALERMO</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="assets/login.js" defer></script>
</head>
<body>
    <aside class="sidebar" aria-label="Identidad del sistema">
        <a class="brand" href="index.php"><span class="drop" aria-hidden="true"></span>AGUAS<br>PALERMO</a>
        <p class="brand-subtitle">Gestión de reclamos</p>
        <div class="sidebar-caption">Servicio de agua<br><span>Acceso administrativo</span></div>
    </aside>
    <div class="workspace">
        <header class="topbar"><span>Sistema de Gestión de Reclamos de Agua</span><span class="topbar-caption">Acceso al sistema</span></header>
        <main class="login-main">
            <div class="page-heading"><p class="eyebrow">ACCESO ADMINISTRATIVO</p><h1>Login de administrador</h1><p>Ingresá para gestionar los reclamos del servicio de agua.</p></div>
            <section class="login-card" aria-labelledby="welcome">
                <div class="card-accent" aria-hidden="true"></div>
                <h2 id="welcome">Bienvenido a AGUAS PALERMO</h2>
                <p class="intro">Ingresá tus credenciales para continuar.</p>
                <?php if ($notice): ?><div class="alert success" role="status"><?= escape($notice) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error" role="alert"><?= escape($error) ?></div><?php endif; ?>
                <form method="post" action="index.php">
                    <input type="hidden" name="csrf" value="<?= escape(csrfToken()) ?>">
                    <label for="usuario">Usuario</label>
                    <input id="usuario" name="usuario" type="text" autocomplete="username" maxlength="80" placeholder="Ingresá tu usuario" value="<?= escape($username) ?>" required>
                    <label for="contrasena">Contraseña</label>
                    <div class="password-field"><input id="contrasena" name="contrasena" type="password" autocomplete="current-password" placeholder="Ingresá tu contraseña" required><button type="button" class="password-toggle" aria-controls="contrasena" aria-label="Mostrar contraseña" aria-pressed="false" hidden>Mostrar</button></div>
                    <button class="primary" type="submit">Iniciar sesión <span aria-hidden="true">→</span></button>
                </form>
                <p class="access-note">Solo administradores autorizados.</p>
            </section>
        </main>
        <footer>AGUAS PALERMO <span>Gestión de reclamos del servicio de agua</span></footer>
    </div>
</body>
</html>
