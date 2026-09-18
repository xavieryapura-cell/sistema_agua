# Primera vista: login de AGUA

Proyecto PHP 8.x, HTML, CSS y JavaScript para Apache de XAMPP y la base sistema_agua ya creada. No requiere Composer, npm ni librerías externas.

## 1. Abrir en Visual Studio Code

Copiá la carpeta sistema_agua dentro de C:\xampp\htdocs. La ruta final debe ser C:\xampp\htdocs\sistema_agua\index.php. Abrí esa carpeta con File > Open Folder en VS Code.

## 2. Revisar la conexión

En config.php completá host, port, user y password con los datos de la conexión de MySQL Workbench donde creaste sistema_agua. La contraseña de esa conexión es distinta de la contraseña del administrador de AGUA.

El archivo trae como ejemplo 127.0.0.1, puerto 3306, usuario root y contraseña vacía. No presupone que esos sean tus datos. No vuelvas a crear la base.

Si tu base está en un servidor MySQL que ya se ejecuta como servicio de Windows, dejalo activo y encendé solamente Apache en XAMPP. No necesitás encender un segundo servidor de base de datos de XAMPP en el mismo puerto. Workbench es el editor con el que administrás el servidor.

## 3. Crear el primer administrador

Abrí Terminal > New Terminal en VS Code. En PowerShell, desde la carpeta del proyecto, ejecutá:

```powershell
& 'C:\xampp\php\php.exe' .\crear_admin.php
```

Completá nombre, apellido, usuario y contraseña. La contraseña se ingresa dos veces y es visible en esta terminal local; se guarda como hash en la base, nunca como texto. No envíes tu contraseña por el chat. El asistente solo crea la primera cuenta y no funciona desde el navegador. Si ya hay usuarios, utilizá una cuenta existente con un hash compatible con password_verify de PHP.

## 4. Abrir el login

Con Apache iniciado, abrí http://localhost/sistema_agua/ en el navegador. Si cambiaste el puerto de Apache, agregalo a la URL, por ejemplo http://localhost:8080/sistema_agua/.

No uses Live Server para esta página: el código PHP se ejecuta mediante Apache de XAMPP.

## Archivos

| Archivo | Función |
|---|---|
| index.php | Vista del login y validación del acceso. |
| assets/styles.css | Diseño azul y blanco, adaptable a teléfono. |
| assets/login.js | Mostrar u ocultar la contraseña. |
| config.php | Datos de conexión al servidor de base de datos. |
| app.php | Conexión PDO, sesiones, consultas y funciones comunes. |
| inicio.php | Bienvenida protegida para comprobar el acceso. Aún no es el panel completo. |
| salir.php | Cierra la sesión y vuelve al login. |
| crear_admin.php | Asistente de terminal para crear el primer administrador. |

## Comprobación en tu equipo

1. Abrir inicio.php sin sesión: debe volver al login.
2. Ingresar datos incorrectos: debe mostrar un mensaje sin revelar si el usuario existe.
3. Ingresar con el administrador activo: debe mostrar la bienvenida con su nombre.
4. Revisar usuarios.ultimo_acceso y el nuevo registro de acceso en log.
5. Cerrar sesión: debe volver al login y mostrar «Sesión cerrada correctamente».
6. Volver a abrir inicio.php: debe requerir autenticación otra vez.
7. Desactivar la cuenta en la base: no debe permitir un nuevo acceso.
8. Probar Mostrar/Ocultar contraseña y el diseño en una ventana angosta.

## Alcance de esta entrega

Solo acceden usuarios administradores. No hay acceso de operarios ni registro público. La conexión usa consultas preparadas, contraseña hasheada, protección CSRF y renovación del identificador de sesión al ingresar/salir. Después de cinco errores hay una pausa de un minuto por sesión de navegador; no es un bloqueo global de cuenta.

Los accesos correctos y cierres de sesión se registran en log. Si falla la base durante el cierre, la sesión se cierra igualmente y el error se registra en el log de PHP. La advertencia de confirmación para cerrar sesión queda pendiente para que la agregues, tal como acordamos.

Esta entrega está preparada para desarrollo local en XAMPP. La comprobación completa del acceso requiere tu servidor y una cuenta creada en él.
