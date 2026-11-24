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

$busqueda = trim($_GET["buscar"] ?? "");

$sql = "SELECT id, nombre, apellido, dni, email, creado_en
        FROM usuarios
        WHERE archivado_en IS NULL";

if ($busqueda !== "") {
    $sql .= " AND (
        nombre LIKE :busqueda
        OR apellido LIKE :busqueda
        OR dni LIKE :busqueda
        OR email LIKE :busqueda
    )";
}

$sql .= " ORDER BY creado_en DESC";

$consulta = $conexion->prepare($sql);

if ($busqueda !== "") {
    $consulta->execute([
        "busqueda" => "%" . $busqueda . "%"
    ]);
} else {
    $consulta->execute();
}

$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de usuarios - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<main>

    <h1>AeroTraslados</h1>
    <h2>Panel de administración</h2>

    <p>Bienvenido/a,
        <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
    </p>

    <h3>Usuarios registrados</h3>

    <form action="admin_usuarios.php" method="GET">

        <label>Buscar usuario</label>

        <input
            type="text"
            name="buscar"
            placeholder="Nombre, apellido, DNI o email"
            value="<?php echo htmlspecialchars($busqueda); ?>"
        >

        <button type="submit">Buscar</button>

        <a href="admin_usuarios.php">Limpiar búsqueda</a>

    </form>

    <br>

    <?php if (count($usuarios) > 0): ?>

        <table border="1" cellpadding="8">

            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>DNI</th>
                <th>Email</th>
                <th>Fecha de registro</th>
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

                    <td>
                        <a href="editar_usuario.php?id=<?php echo (int) $usuario["id"]; ?>">
                            Editar
                        </a>

                        |

                        <a href="archivar_usuario.php?id=<?php echo (int) $usuario["id"]; ?>">
                            Archivar
                        </a>
                        |
<a href="eliminar_usuario.php?id=<?php echo (int) $usuario["id"]; ?>">
    Eliminar
</a>
                    </td>
                

                </tr>

            <?php endforeach; ?>

        </table>

    <?php else: ?>

        <p>No se encontraron usuarios.</p>

    <?php endif; ?>

    <br>

    <a href="usuarios_archivados.php">Ver usuarios archivados</a>

    <br><br>

<a href="agregar_usuario.php">Agregar cuenta de usuario</a>

    <br><br>

<a href="admin_reservas.php">Administración de reservas</a>

<br><br>

    <a href="cerrar_sesion.php">Cerrar sesión</a>

</main>

</body>
</html>