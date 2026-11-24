
<?php
session_start();

// Comprobar que sea administrador
if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

require_once "conexion.php";

// Crear token CSRF
if (!isset($_SESSION["csrf_agregar"])) {
    $_SESSION["csrf_agregar"] = bin2hex(random_bytes(32));
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar token CSRF
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_agregar"], $token)) {
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

            // Encriptar contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Crear cuenta de usuario normal
            $insertar = $conexion->prepare(
                "INSERT INTO usuarios
                (nombre, apellido, dni, email, password_hash, rol)
                VALUES (?, ?, ?, ?, ?, 'usuario')"
            );

            $insertar->execute([
                $nombre,
                $apellido,
                $dni,
                $email,
                $passwordHash
            ]);

            $mensaje = "La cuenta de usuario se creó correctamente.";

            // Renovar token CSRF
            $_SESSION["csrf_agregar"] = bin2hex(random_bytes(32));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agregar usuario - AeroTraslados</title>

    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

<main>

    <h1>AeroTraslados</h1>

    <h2>Agregar cuenta de usuario</h2>

    <?php if ($mensaje !== ""): ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="agregar_usuario.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_agregar"]); ?>"
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

        <button type="submit">Crear cuenta</button>

    </form>

    <br>

    <a href="admin_usuarios.php">Volver al panel de administración</a>

</main>

</body>
</html>
