
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

// Crear token CSRF si todavía no existe
if (!isset($_SESSION["csrf_reactivar"])) {
    $_SESSION["csrf_reactivar"] = bin2hex(random_bytes(32));
}

// Consultar usuarios archivados
$consulta = $conexion->prepare(
    "SELECT id, nombre, apellido, dni, email, creado_en, archivado_en
     FROM usuarios
     WHERE archivado_en IS NOT NULL
     ORDER BY archivado_en DESC"
);

$consulta->execute();

$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios archivados - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<main>

    <h1>AeroTraslados</h1>
    <h2>Usuarios archivados</h2>

    <?php if (count($usuarios) > 0): ?>

        <table border="1" cellpadding="8">

            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>DNI</th>
                <th>Email</th>
                <th>Fecha de registro</th>
                <th>Fecha de archivado</th>
                <th>Acciones</th>
            </tr>

            <?php foreach ($usuarios as $usuario): ?>

                <tr>
                    <td><?php echo (int) $usuario["id"]; ?></td>

                    <td><?php echo htmlspecialchars($usuario["nombre"]); ?></td>

                    <td><?php echo htmlspecialchars($usuario["apellido"]); ?></td>

                    <td><?php echo htmlspecialchars($usuario["dni"]); ?></td>

                    <td><?php echo htmlspecialchars($usuario["email"]); ?></td>

                    <td><?php echo htmlspecialchars($usuario["creado_en"]); ?></td>

                    <td><?php echo htmlspecialchars($usuario["archivado_en"]); ?></td>

                    <td>
                        <form action="reactivar_usuario.php" method="POST">

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo (int) $usuario["id"]; ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION["csrf_reactivar"]); ?>"
                            >

                            <button type="submit">Reactivar</button>

                        </form>
                    </td>
                </tr>

            <?php endforeach; ?>

        </table>

    <?php else: ?>

        <p>No hay usuarios archivados.</p>

    <?php endif; ?>

    <br>

    <a href="admin_usuarios.php">Volver al panel administrativo</a>

    <br><br>

    <a href="cerrar_sesion.php">Cerrar sesión</a>

</main>

</body>
</html>
