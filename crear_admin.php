<?php
declare(strict_types=1);

// Solo se puede ejecutar desde la terminal; no permite altas por navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/app.php';

function ask(string $prompt): string
{
    echo $prompt;
    return trim((string) fgets(STDIN));
}

try {
    $pdo = db();
    if ((int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
        exit("Ya existe un administrador. Este asistente solo crea la primera cuenta.\n");
    }
    $name = ask('Nombre: ');
    $surname = ask('Apellido: ');
    $username = ask('Usuario (sin espacios): ');
    echo "La contraseña será visible en esta terminal local.\n";
    $password = ask('Contraseña (12 a 72 bytes, sin espacios al inicio/final): ');
    $confirmation = ask('Repetí la contraseña: ');
    if ($name === '' || $surname === '' || strlen($name) > 80 || strlen($surname) > 80
        || !preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username)
        || strlen($password) < 12 || strlen($password) > 72 || $password !== $confirmation) {
        exit("Datos inválidos. Revisá los campos y volvé a ejecutar el asistente.\n");
    }
    $query = $pdo->prepare('INSERT INTO usuarios (nombre, apellido, usuario, contrasena_hash) VALUES (?, ?, ?, ?)');
    $query->execute([$name, $surname, $username, password_hash($password, PASSWORD_DEFAULT)]);
    echo "Administrador creado. Ya podés ingresar desde el navegador.\n";
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    exit("No se pudo crear la cuenta. Revisá config.php, la conexión y la tabla usuarios.\n");
}
