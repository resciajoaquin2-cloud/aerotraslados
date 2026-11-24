<?php
session_start();

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

require_once "conexion.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: admin_usuarios.php");
    exit;
}

// Buscar el usuario que se quiere editar
$consulta = $conexion->prepare(
    "SELECT id, nombre, apellido, dni, email
     FROM usuarios
     WHERE id = ? AND archivado_en IS NULL"
);

$consulta->execute([$id]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit;
}

$mensaje = "";

// Crear token de seguridad
if (!isset($_SESSION["csrf_editar"])) {
    $_SESSION["csrf_editar"] = bin2hex(random_bytes(32));
}

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_editar"], $token)) {
        die("Solicitud no válida.");
    }

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $dni = trim($_POST["dni"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if (
        $nombre === "" ||
        $apellido === "" ||
        $dni === "" ||
        $email === ""
    ) {
        $mensaje = "Todos los campos son obligatorios.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresá un correo electrónico válido.";

    } else {

        $comprobar = $conexion->prepare(
            "SELECT id FROM usuarios
             WHERE (dni = ? OR email = ?) AND id != ?"
        );

        $comprobar->execute([$dni, $email, $id]);

        if ($comprobar->fetch()) {
            $mensaje = "Ese DNI o correo ya pertenece a otro usuario.";

        } else {

            $actualizar = $conexion->prepare(
                "UPDATE usuarios
                 SET nombre = ?, apellido = ?, dni = ?, email = ?
                 WHERE id = ? AND archivado_en IS NULL"
            );

            $actualizar->execute([
                $nombre,
                $apellido,
                $dni,
                $email,
                $id
            ]);

            unset($_SESSION["csrf_editar"]);

            header("Location: admin_usuarios.php");
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
    <title>Editar usuario - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<main>

    <h1>AeroTraslados</h1>
    <h2>Editar usuario</h2>

    <?php if ($mensaje !== ""): ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="editar_usuario.php?id=<?php echo (int) $id; ?>" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_editar"]); ?>"
        >

        <label>Nombre</label>
        <input
            type="text"
            name="nombre"
            value="<?php echo htmlspecialchars($usuario["nombre"]); ?>"
            required
        >

        <label>Apellido</label>
        <input
            type="text"
            name="apellido"
            value="<?php echo htmlspecialchars($usuario["apellido"]); ?>"
            required
        >

        <label>DNI</label>
        <input
            type="text"
            name="dni"
            value="<?php echo htmlspecialchars($usuario["dni"]); ?>"
            required
        >

        <label>Correo electrónico</label>
        <input
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($usuario["email"]); ?>"
            required
        >

        <button type="submit">Guardar cambios</button>

    </form>

    <br>

    <a href="admin_usuarios.php">Volver al panel administrativo</a>

</main>

</body>
</html>