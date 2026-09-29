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

//Esta consulta une cada reclamo con su vecino y trae los cinco más recientes. Si falla la conexión, muestra un mensaje distinto al de una lista vacía.
$reclamos = [];
$errorReclamos = '';

try {
    $consulta = db()->query("
        SELECT
            r.id_reclamo,
            r.fecha_hora,
            v.nombre,
            v.apellido,
            r.tipo_problema,
            r.prioridad,
            r.estado
        FROM reclamos AS r
        INNER JOIN vecinos AS v
            ON r.id_vecino = v.id_vecino
        ORDER BY r.fecha_hora DESC, r.id_reclamo DESC
        LIMIT 5
    ");

    $reclamos = $consulta->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorReclamos = 'No se pudieron cargar los reclamos. Intentá nuevamente.';
}

$clasesEstado = [
    'Pendiente' => 'pendiente',
    'Asignado' => 'asignado',
    'En proceso' => 'en-proceso',
    'Resuelto' => 'resuelto',
    'No resuelto' => 'no-resuelto',
];

$clasesPrioridad = [
    'Alta' => 'alta',
    'Media' => 'media',
    'Baja' => 'baja',
];
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inicio | AGUAS</title>
<link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="assets/styles.css">
</head>
<body class="bootstrap-page">
    <aside
    class="offcanvas-lg offcanvas-start bs-sidebar"
    tabindex="-1"
    id="menuLateral"
    aria-labelledby="tituloMenu"
>
    <div class="offcanvas-header">
        <h2 class="offcanvas-title fs-5" id="tituloMenu">
            Menú principal
        </h2>

        <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"
            data-bs-target="#menuLateral"
            aria-label="Cerrar menú"
        ></button>
    </div>

    <div class="offcanvas-body">
        <a class="brand" href="inicio.php">
            <span class="drop" aria-hidden="true"></span>
            <span class="brand-name">AGUAS<br>PALERMO</span>
        </a>

        <p class="brand-subtitle">Gestión de reclamos</p>

        <nav class="nav nav-pills flex-column gap-2 mt-4">
            <a
                class="nav-link active"
                href="inicio.php"
                aria-current="page"
            >
                Inicio
            </a>

            <a class="nav-link" href="nuevo_vecino.php">
                Nuevo vecino
            </a>

            <a class="nav-link" href="nuevo_operario.php">
                Nuevo operario
            </a>
        </nav>

        <form
            class="mt-auto pt-4"
            action="salir.php"
            method="post"
        >
            <input
                type="hidden"
                name="csrf"
                value="<?= escape(csrfToken()) ?>"
            >

            <button type="submit" class="btn btn-outline-light w-100">
                Cerrar sesión
            </button>
        </form>
    </div>
    </aside>
    <div class="workspace">
        <header class="topbar">
        <button
    class="btn btn-outline-light d-lg-none"
    type="button"
    data-bs-toggle="offcanvas"
    data-bs-target="#menuLateral"
    aria-controls="menuLateral"
>
    ☰ Menú
    </button>    
        <span>Sistema de Gestión de Reclamos de Agua</span>
        <span class="topbar-caption"><?= escape($admin['nombre'] . ' ' . $admin['apellido']) ?> · Administrador</span>
    </header>
        <main class="login-main dashboard">
    <div class="page-heading">
        <p class="eyebrow">INICIO</p>
        <h1>Hola, <?= escape($admin['nombre']) ?></h1>
        <p>Consultá los últimos reclamos y accedé a las tareas habituales.</p>
    </div>

    <section class="panel-card" aria-labelledby="titulo-accesos">
        <h2 id="titulo-accesos">Accesos rápidos</h2>

        <div class="row g-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <button class="btn btn-primary w-100 py-3" type="button" disabled>
            + Nuevo reclamo
        </button>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <a class="btn btn-primary w-100 py-3" href="nuevo_vecino.php">
            + Nuevo vecino
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <a class="btn btn-primary w-100 py-3" href="nuevo_operario.php">
            + Nuevo operario
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <button class="btn btn-primary w-100 py-3" type="button" disabled>
            Reportes
        </button>
    </div>
    </div>

        <p class="panel-note">
            Nuevo reclamo y Reportes se habilitarán en las próximas etapas.
        </p>
    </section>

    <section class="panel-card" aria-labelledby="titulo-reclamos">
        <h2 id="titulo-reclamos">Reclamos recientes</h2>

        <?php if ($errorReclamos !== ''): ?>

            <div class="alert error" role="alert">
                <?= escape($errorReclamos) ?>
            </div>

        <?php elseif (empty($reclamos)): ?>

            <div class="empty-state">
                <h3>Todavía no hay reclamos registrados</h3>
                <p>Cuando cargues el primero, aparecerá en esta sección.</p>
            </div>

        <?php else: ?>

            <div
                class="table-responsive"
                role="region"
                aria-label="Reclamos recientes"
                tabindex="0">
                <table class="claims-table">
                    <thead>
                        <tr>
                            <th scope="col">Reclamo</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Vecino</th>
                            <th scope="col">Problema</th>
                            <th scope="col">Prioridad</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($reclamos as $reclamo): ?>
                            <tr>
                                <td>
                                    #<?= (int) $reclamo['id_reclamo'] ?>
                                </td>

                                <td>
                                    <?= escape(
                                        date('d/m/Y H:i', strtotime($reclamo['fecha_hora']))
                                    ) ?>
                                </td>

                                <td>
                                    <?= escape(
                                        $reclamo['nombre'] . ' ' . $reclamo['apellido']
                                    ) ?>
                                </td>

                                <td>
                                    <?= escape($reclamo['tipo_problema']) ?>
                                </td>

                                <td>
                                    <span class="badge <?= escape(
                                        $clasesPrioridad[$reclamo['prioridad']] ?? ''
                                    ) ?>">
                                        <?= escape($reclamo['prioridad']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge <?= escape(
                                        $clasesEstado[$reclamo['estado']] ?? ''
                                    ) ?>">
                                        <?= escape($reclamo['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>
</main>
        <footer>AGUA <span>Gestión de reclamos del servicio de agua</span></footer>
    </div>
<script src="assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
