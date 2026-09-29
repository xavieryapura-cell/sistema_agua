<?php
declare(strict_types=1);

require __DIR__ . '/app.php';
startSession();

// Verificar que haya un administrador conectado.
try {
    $admin = currentAdmin();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(503);
    exit('No se pudo verificar la sesión. Intentá nuevamente.');
}

if (!$admin) {
    header('Location: index.php');
    exit;
}

// Valores iniciales del formulario.
$datos = [
    'nombre' => '',
    'apellido' => '',
    'telefono' => '',
    'especialidad' => '',
    'estado' => 'Activo',
    'observaciones' => '',
];

$errores = [];
$mensaje = $_SESSION['mensaje_operario'] ?? '';
unset($_SESSION['mensaje_operario']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $campo => $valor) {
        $entrada = $_POST[$campo] ?? '';
        $datos[$campo] = is_string($entrada) ? trim($entrada) : '';
    }

    if (!validCsrf()) {
        $errores[] = 'El formulario venció. Recargá la página e intentá nuevamente.';
    }

    $obligatorios = [
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'telefono' => 'Teléfono',
        'especialidad' => 'Especialidad',
    ];

    foreach ($obligatorios as $campo => $etiqueta) {
        if ($datos[$campo] === '') {
            $errores[] = "Completá el campo {$etiqueta}.";
        }
    }

    $limites = [
        'nombre' => 80,
        'apellido' => 80,
        'telefono' => 40,
        'especialidad' => 100,
    ];

    foreach ($limites as $campo => $limite) {
        if (mb_strlen($datos[$campo], 'UTF-8') > $limite) {
            $errores[] = "El campo {$campo} supera los {$limite} caracteres.";
        }
    }

    if (!in_array($datos['estado'], ['Activo', 'Inactivo'], true)) {
        $errores[] = 'Seleccioná un estado válido.';
    }

    if (strlen($datos['observaciones']) > 65535) {
        $errores[] = 'Las observaciones son demasiado extensas.';
    }

    if (empty($errores)) {
        $pdo = null;

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $consulta = $pdo->prepare("
                INSERT INTO operarios (
                    nombre,
                    apellido,
                    telefono,
                    especialidad,
                    estado,
                    observaciones
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $consulta->execute([
                $datos['nombre'],
                $datos['apellido'],
                $datos['telefono'],
                $datos['especialidad'],
                $datos['estado'],
                $datos['observaciones'] !== ''
                    ? $datos['observaciones'] : null,
            ]);

            $idOperario = (int) $pdo->lastInsertId();

            // Registrar la acción del administrador.
            $registroLog = $pdo->prepare("
                INSERT INTO `log` (
                    id_usuario,
                    modulo,
                    accion,
                    registro_afectado,
                    descripcion
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $registroLog->execute([
                $admin['id_usuario'],
                'Operarios',
                'Registro',
                'operarios:' . $idOperario,
                'Registró un nuevo operario.',
            ]);

            $pdo->commit();

            $_SESSION['mensaje_operario'] =
                "Operario #{$idOperario} registrado correctamente.";

            header('Location: nuevo_operario.php');
            exit;
        } catch (PDOException $exception) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($exception->getMessage());
            $errores[] = 'No se pudo guardar el operario. Intentá nuevamente.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo operario | Aguas Palermo</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="inicio.php">
            <span class="drop" aria-hidden="true"></span>
            <span>AGUAS<br>PALERMO</span>
        </a>

        <p class="brand-subtitle">Gestión de reclamos</p>

        <nav class="side-menu">
            <a href="inicio.php">Inicio</a>
            <a href="nuevo_vecino.php">Nuevo vecino</a>

            <a class="nav-active"
               href="nuevo_operario.php"
               aria-current="page">
                Nuevo operario
            </a>
        </nav>

        <form class="logout" action="salir.php" method="post">
            <input
                type="hidden"
                name="csrf"
                value="<?= escape(csrfToken()) ?>"
            >
            <button type="submit">Cerrar sesión</button>
        </form>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <span>Sistema de Gestión de Reclamos de Agua</span>

            <span class="topbar-caption">
                <?= escape($admin['nombre'] . ' ' . $admin['apellido']) ?>
                · Administrador
            </span>
        </header>

        <main class="login-main dashboard">
            <div class="page-heading">
                <p class="eyebrow">OPERARIOS</p>
                <h1>Nuevo operario</h1>
                <p>Registrá al personal que atenderá los reclamos.</p>
            </div>

            <section class="panel-card" aria-labelledby="titulo-formulario">
                <h2 id="titulo-formulario">Datos del operario</h2>

                <?php if ($mensaje !== ''): ?>
                    <div class="alert success" role="status">
                        <?= escape($mensaje) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                    <div class="alert error" role="alert">
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?= escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <p class="panel-note">
                    Los campos con * son obligatorios.
                </p>

                <form method="post" action="nuevo_operario.php">
                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= escape(csrfToken()) ?>"
                    >

                    <div class="form-grid">
                        <div>
                            <label for="nombre">Nombre *</label>
                            <input
                                id="nombre"
                                name="nombre"
                                type="text"
                                maxlength="80"
                                autocomplete="given-name"
                                value="<?= escape($datos['nombre']) ?>"
                                required
                            >
                        </div>

                        <div>
                            <label for="apellido">Apellido *</label>
                            <input
                                id="apellido"
                                name="apellido"
                                type="text"
                                maxlength="80"
                                autocomplete="family-name"
                                value="<?= escape($datos['apellido']) ?>"
                                required
                            >
                        </div>

                        <div>
                            <label for="telefono">Teléfono *</label>
                            <input
                                id="telefono"
                                name="telefono"
                                type="tel"
                                maxlength="40"
                                autocomplete="tel"
                                value="<?= escape($datos['telefono']) ?>"
                                required
                            >
                        </div>

                        <div>
                            <label for="especialidad">Especialidad *</label>
                            <input
                                id="especialidad"
                                name="especialidad"
                                type="text"
                                maxlength="100"
                                placeholder="Por ejemplo: Plomería"
                                value="<?= escape($datos['especialidad']) ?>"
                                required
                            >
                        </div>

                        <div>
                            <label for="estado">Estado *</label>
                            <select id="estado" name="estado" required>
                                <option
                                    value="Activo"
                                    <?= $datos['estado'] === 'Activo'
                                        ? 'selected' : '' ?>
                                >
                                    Activo
                                </option>

                                <option
                                    value="Inactivo"
                                    <?= $datos['estado'] === 'Inactivo'
                                        ? 'selected' : '' ?>
                                >
                                    Inactivo
                                </option>
                            </select>

                            <p class="panel-note">
                                Solo los operarios activos podrán recibir
                                nuevas asignaciones.
                            </p>
                        </div>

                        <div class="full-width">
                            <label for="observaciones">Observaciones</label>
                            <textarea
                                id="observaciones"
                                name="observaciones"
                                rows="4"
                                placeholder="Por ejemplo: disponible en turno mañana"
                            ><?= escape($datos['observaciones']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="primary" type="submit">
                            Guardar operario
                        </button>

                        <a class="secondary-button" href="inicio.php">
                            Cancelar
                        </a>
                    </div>
                </form>
            </section>
        </main>

        <footer>
            AGUAS PALERMO
            <span>Gestión de reclamos del servicio de agua</span>
        </footer>
    </div>
</body>
</html> 