<?php
session_start();
require_once "conexion.php";


if (!isset($_SESSION["csrf_login"])) {
    $_SESSION["csrf_login"] = bin2hex(random_bytes(32));
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

   
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_login"], $token)) {
        die("Solicitud no válida.");
    }

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $mensaje = "Completá todos los campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "Ingresá un correo electrónico válido.";

    } else {

        $consulta = $conexion->prepare(
            "SELECT id, nombre, apellido, email, password_hash, rol
             FROM usuarios
             WHERE email = ? AND archivado_en IS NULL"
        );

        $consulta->execute([$email]);
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario["password_hash"])) {

            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["apellido"] = $usuario["apellido"];
            $_SESSION["email"] = $usuario["email"];
            $_SESSION["rol"] = $usuario["rol"];

            if ($usuario["rol"] === "admin") {
                header("Location: admin_usuarios.php");
            } else {
                header("Location: panel.php");
            }

            exit;

        } else {

            $mensaje = "Correo o contraseña incorrectos.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <main>

        <h1>AeroTraslados</h1>
        <h2>Iniciar sesión</h2>

        <p>Ingresá a tu cuenta para gestionar tus traslados.</p>

        <?php if ($mensaje !== ""): ?>
            <p><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_login"]); ?>"
            >

            <label>Correo electrónico</label>
            <input type="email" name="email" required>

            <label>Contraseña</label>
            <input type="password" name="password" required>

            <button type="submit">Ingresar</button>

        </form>

        <p>¿Todavía no tenés una cuenta?
            <a href="registro.php">Registrate acá</a>
        </p>

    </main>

</body>
</html>