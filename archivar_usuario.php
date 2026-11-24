
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

// Obtener el ID del usuario
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id == $_SESSION["usuario_id"]) {
    header("Location: admin_usuarios.php");
    exit;
}

// Crear token CSRF
if (!isset($_SESSION["csrf_archivar"])) {
    $_SESSION["csrf_archivar"] = bin2hex(random_bytes(32));
}

// Procesar el archivado
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_archivar"], $token)) {
        die("Solicitud no válida.");
    }

    $consulta = $conexion->prepare(
        "UPDATE usuarios
         SET archivado_en = NOW()
         WHERE id = ?
         AND archivado_en IS NULL"
    );

    $consulta->execute([$id]);

    unset($_SESSION["csrf_archivar"]);

    header("Location: admin_usuarios.php");
    exit;
}

// Buscar el usuario
$consulta = $conexion->prepare(
    "SELECT nombre, apellido
     FROM usuarios
     WHERE id = ? AND archivado_en IS NULL"
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
    <title>Archivar usuario - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<main>

    <h1>AeroTraslados</h1>
    <h2>Archivar usuario</h2>

    <p>
        ¿Estás seguro de que querés archivar a
        <strong>
            <?php echo htmlspecialchars($usuario["nombre"] . " " . $usuario["apellido"]); ?>
        </strong>?
    </p>

    <p>La cuenta dejará de estar activa, pero sus datos no se eliminarán.</p>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_archivar"]); ?>"
        >

        <button type="submit">Confirmar archivado</button>

    </form>

    <br>

    <a href="admin_usuarios.php">Cancelar y volver</a>

</main>

</body>
</html>
