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


$consulta = $conexion->prepare(
    "SELECT
        reservas.*,
        usuarios.nombre,
        usuarios.apellido,
        usuarios.email
     FROM reservas
     INNER JOIN usuarios
        ON reservas.usuario_id = usuarios.id
     ORDER BY reservas.creado_en DESC"
);

$consulta->execute();
$reservas = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar reservas - AeroTraslados</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Administración de reservas</h1>

    <p>Desde aquí podés consultar todas las reservas realizadas.</p>

    <p>
        <a href="admin_usuarios.php">Volver a administración de usuarios</a>
    </p>

    <?php if (count($reservas) > 0): ?>

        <table border="1" cellpadding="8">
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Email</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Pasajeros</th>
                <th>Observaciones</th>
                <th>Estado</th>
            </tr>

            <?php foreach ($reservas as $reserva): ?>
                <tr>
                    <td>
                        <?php echo (int) $reserva["id"]; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $reserva["nombre"] . " " . $reserva["apellido"]
                        );
                        ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($reserva["email"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($reserva["fecha_traslado"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($reserva["hora_traslado"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($reserva["origen"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($reserva["destino"]); ?>
                    </td>

                    <td>
                        <?php echo (int) $reserva["pasajeros"]; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $reserva["observaciones"] ?? ""
                        );
                        ?>
                    </td>

                    <td>
    <form action="actualizar_estado_reserva.php" method="POST">

        <?php
        if (!isset($_SESSION["csrf_reservas"])) {
            $_SESSION["csrf_reservas"] = bin2hex(random_bytes(32));
        }
        ?>

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_reservas"]); ?>"
        >

        <input
            type="hidden"
            name="id"
            value="<?php echo (int) $reserva["id"]; ?>"
        >

        <select name="estado">
            <option value="pendiente" <?php echo $reserva["estado"] === "pendiente" ? "selected" : ""; ?>>
                Pendiente
            </option>

            <option value="confirmada" <?php echo $reserva["estado"] === "confirmada" ? "selected" : ""; ?>>
                Confirmada
            </option>

            <option value="cancelada" <?php echo $reserva["estado"] === "cancelada" ? "selected" : ""; ?>>
                Cancelada
            </option>

            <option value="completada" <?php echo $reserva["estado"] === "completada" ? "selected" : ""; ?>>
                Completada
            </option>
        </select>

        <button type="submit">Guardar estado</button>

    </form>
</td>
                </tr>
            <?php endforeach; ?>

        </table>

    <?php else: ?>

        <p>Todavía no hay reservas registradas.</p>

    <?php endif; ?>

</body>
</html>