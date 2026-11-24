<?php
session_start();

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "usuario"
) {
    header("Location: login.php");
    exit;
}

require_once "conexion.php";

// Obtener los datos actualizados del usuario
$consulta = $conexion->prepare(
    "SELECT nombre, apellido, email
     FROM usuarios
     WHERE id = ? AND archivado_en IS NULL"
);

$consulta->execute([$_SESSION["usuario_id"]]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi cuenta - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <main>

        <h1>AeroTraslados</h1>

        <h2>Mi cuenta</h2>

        <p>Bienvenido/a,
            <?php echo htmlspecialchars($usuario["nombre"] . " " . $usuario["apellido"]); ?>
        </p>

        <h3>Mis datos personales</h3>

        <p><strong>Nombre:</strong>
            <?php echo htmlspecialchars($usuario["nombre"]); ?>
        </p>

        <p><strong>Apellido:</strong>
            <?php echo htmlspecialchars($usuario["apellido"]); ?>
        </p>

        <p><strong>Correo:</strong>
            <?php echo htmlspecialchars($usuario["email"]); ?>
        </p>

        <h3>Mis traslados</h3>

        <p>Desde acá vas a poder gestionar tus reservas.</p>

        <a href="reservas.php">Ver mis reservas</a>

        <br><br>

        <a href="cerrar_sesion.php">Cerrar sesión</a>

    </main>

</body>
</html>