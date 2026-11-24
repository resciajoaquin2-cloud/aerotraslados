
<?php
session_start();

require_once "conexion.php";

// Crear token CSRF
if (!isset($_SESSION["csrf_registro"])) {
    $_SESSION["csrf_registro"] = bin2hex(random_bytes(32));
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar token CSRF
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_registro"], $token)) {
        die("Solicitud no válida.");
    }

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $dni = trim($_POST["dni"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $nombre === "" ||
        $apellido === "" ||
        $dni === "" ||
        $email === "" ||
        $password === ""
    ) {
        $mensaje = "Todos los campos son obligatorios.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresá un correo electrónico válido.";

    } elseif (strlen($password) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";

    } else {

        // Comprobar si el DNI o email ya existen
        $consulta = $conexion->prepare(
            "SELECT id FROM usuarios WHERE dni = ? OR email = ?"
        );

        $consulta->execute([$dni, $email]);

        if ($consulta->fetch()) {
            $mensaje = "El DNI o el correo ya están registrados.";

        } else {

            // Encriptar la contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Registrar al usuario
            $insertar = $conexion->prepare(
                "INSERT INTO usuarios
                (nombre, apellido, dni, email, password_hash)
                VALUES (?, ?, ?, ?, ?)"
            );

            $insertar->execute([
                $nombre,
                $apellido,
                $dni,
                $email,
                $passwordHash
            ]);

            // Obtener el ID del usuario recién registrado
            $usuarioId = (int) $conexion->lastInsertId();

            // Iniciar sesión automáticamente
            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $usuarioId;
            $_SESSION["rol"] = "usuario";
            $_SESSION["nombre"] = $nombre;

            // Eliminar el token de registro
            unset($_SESSION["csrf_registro"]);

            // Ir directamente a reservar un traslado
            header("Location: reservas.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro - AeroTraslados</title>

    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <main>

        <h1>Crear una cuenta</h1>

        <p>Registrate para reservar tus traslados.</p>

        <?php if ($mensaje !== ""): ?>

            <p><?php echo htmlspecialchars($mensaje); ?></p>

        <?php endif; ?>

        <form action="registro.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_registro"]); ?>"
            >

            <label>Nombre</label>
            <input type="text" name="nombre" required>

            <label>Apellido</label>
            <input type="text" name="apellido" required>

            <label>DNI</label>
            <input type="text" name="dni" required>

            <label>Correo electrónico</label>
            <input type="email" name="email" required>

            <label>Contraseña</label>
            <input type="password" name="password" minlength="8" required>

            <button type="submit">Registrarme</button>

        </form>

        <p>
            ¿Ya tenés una cuenta?
            <a href="login.php">Iniciá sesión</a>
        </p>

    </main>

</body>

</html>
