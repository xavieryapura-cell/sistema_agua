<?php
declare(strict_types=1);
require __DIR__ . '/app.php';
startSession();
try {
    $admin = currentAdmin();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(503);
    exit('No se pudo verificar la sesión. Intentá nuevamente en unos minutos.');
}
if (!$admin) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Inicio | AGUAS</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
    <aside class="sidebar"><a class="brand" href="inicio.php"><span class="drop" aria-hidden="true"></span>AGUAS PALERMO</a><p class="brand-subtitle">Gestión de reclamos</p><nav><a class="nav-active" href="inicio.php" aria-current="page">Inicio</a></nav><form class="logout" action="salir.php" method="post"><input type="hidden" name="csrf" value="<?= escape(csrfToken()) ?>"><button type="submit">Cerrar sesión</button></form></aside>
    <div class="workspace"><header class="topbar"><span>Sistema de Gestión de Reclamos de Agua</span><span class="topbar-caption"><?= escape($admin['nombre'] . ' ' . $admin['apellido']) ?> · Administrador</span></header>
        <main class="login-main"><div class="page-heading"><p class="eyebrow">INICIO</p><h1>Hola, <?= escape($admin['nombre']) ?></h1><p>Ingresaste correctamente al sistema.</p></div>
            <section class="login-card welcome-card"><h2>Tu sesión está activa</h2><p>Esta es la pantalla de bienvenida de la primera etapa.</p><p>El panel principal y la gestión de reclamos se incorporarán en las siguientes vistas.</p></section>
        </main><footer>AGUA <span>Gestión de reclamos del servicio de agua</span></footer>
    </div>
</body>
</html>
