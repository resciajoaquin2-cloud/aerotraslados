
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

// Obtener el ID del usuario
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id == $_SESSION["usuario_id"]) {
    header("Location: admin_usuarios.php");
    exit;
}

// Crear token CSRF
if (!isset($_SESSION["csrf_eliminar"])) {
    $_SESSION["csrf_eliminar"] = bin2hex(random_bytes(32));
}

// Procesar la eliminación
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

    if (
        !hash_equals($_SESSION["csrf_eliminar"], $token) ||
        !$id ||
        $id == $_SESSION["usuario_id"]
    ) {
        die("Solicitud no válida.");
    }

    try {
        $conexion->beginTransaction();

        // Eliminar las reservas del usuario
        $consulta = $conexion->prepare(
            "DELETE FROM reservas WHERE usuario_id = ?"
        );
        $consulta->execute([$id]);

        // Eliminar el usuario
        $consulta = $conexion->prepare(
            "DELETE FROM usuarios WHERE id = ?"
        );
        $consulta->execute([$id]);

        $conexion->commit();

        unset($_SESSION["csrf_eliminar"]);

        header("Location: admin_usuarios.php");
        exit;

    } catch (PDOException $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        die("No se pudo eliminar el usuario.");
    }
}

// Buscar los datos del usuario
$consulta = $conexion->prepare(
    "SELECT nombre, apellido
     FROM usuarios
     WHERE id = ?"
);

$consulta->execute([$id]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar usuario - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<main>

    <h1>AeroTraslados</h1>
    <h2>Eliminar usuario</h2>

    <p>
        ¿Estás seguro de que querés eliminar a
        <strong>
            <?php echo htmlspecialchars($usuario["nombre"] . " " . $usuario["apellido"]); ?>
        </strong>?
    </p>

    <p>
        Se eliminarán definitivamente su cuenta y todas sus reservas.
        Esta acción no se puede deshacer.
    </p>

    <form method="POST">

        <input
            type="hidden"
            name="id"
            value="<?php echo (int) $id; ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_eliminar"]); ?>"
        >

        <button type="submit">Eliminar definitivamente</button>

    </form>

    <br>

    <a href="admin_usuarios.php">Cancelar y volver</a>

</main>

</body>
</html>
