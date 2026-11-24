
<?php
session_start();

// Comprobar que haya una sesión iniciada
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

// Salir del modo de reserva del administrador
if (
    isset($_GET["salir_admin"]) &&
    $_SESSION["rol"] === "admin"
) {
    unset($_SESSION["reserva_para_usuario_id"]);

    header("Location: admin_usuarios.php");
    exit;
}

require_once "conexion.php";

// Determinar para quién se realizará la reserva
$modoAdministrador = (
    isset($_SESSION["rol"]) &&
    $_SESSION["rol"] === "admin" &&
    isset($_SESSION["reserva_para_usuario_id"])
);

if ($modoAdministrador) {

    $usuarioReservaId = (int) $_SESSION["reserva_para_usuario_id"];

    // Comprobar que el usuario exista y esté activo
    $consulta = $conexion->prepare(
        "SELECT id, nombre, apellido
         FROM usuarios
         WHERE id = ? AND archivado_en IS NULL
         AND rol = 'usuario'"
    );

    $consulta->execute([$usuarioReservaId]);
    $usuarioReserva = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioReserva) {
        unset($_SESSION["reserva_para_usuario_id"]);

        header("Location: admin_usuarios.php");
        exit;
    }

} else {

    // En el modo normal, la reserva pertenece al usuario conectado
    $usuarioReservaId = (int) $_SESSION["usuario_id"];
}

// Crear token CSRF
if (!isset($_SESSION["csrf_reservas"])) {
    $_SESSION["csrf_reservas"] = bin2hex(random_bytes(32));
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar token CSRF
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_reservas"], $token)) {
        die("Solicitud no válida.");
    }

    $fecha = $_POST["fecha"] ?? "";
    $hora = $_POST["hora"] ?? "";
    $origen = trim($_POST["origen"] ?? "");
    $destino = trim($_POST["destino"] ?? "");
    $pasajeros = $_POST["pasajeros"] ?? "";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if (
        $fecha === "" ||
        $hora === "" ||
        $origen === "" ||
        $destino === "" ||
        $pasajeros === ""
    ) {
        $mensaje = "Completá todos los campos obligatorios.";

    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ||
        !checkdate(
            (int) substr($fecha, 5, 2),
            (int) substr($fecha, 8, 2),
            (int) substr($fecha, 0, 4)
        )
    ) {
        $mensaje = "Ingresá una fecha válida.";

    } elseif (
        !preg_match('/^\d{2}:\d{2}$/', $hora) ||
        (int) substr($hora, 0, 2) > 23 ||
        (int) substr($hora, 3, 2) > 59
    ) {
        $mensaje = "Ingresá una hora válida.";

    } elseif (
        !filter_var($pasajeros, FILTER_VALIDATE_INT) ||
        (int) $pasajeros < 1 ||
        (int) $pasajeros > 20
    ) {
        $mensaje = "La cantidad de pasajeros debe estar entre 1 y 20.";

    } elseif (strtotime($fecha . " " . $hora) <= time()) {
        $mensaje = "El traslado debe tener una fecha y hora futuras.";

    } else {

        // Registrar la reserva a nombre del usuario correspondiente
        $consulta = $conexion->prepare(
            "INSERT INTO reservas
            (usuario_id, fecha_traslado, hora_traslado, origen,
             destino, pasajeros, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $consulta->execute([
            $usuarioReservaId,
            $fecha,
            $hora,
            $origen,
            $destino,
            (int) $pasajeros,
            $observaciones !== "" ? $observaciones : null
        ]);

        if ($modoAdministrador) {
            header("Location: reservas.php?modo=admin&creada=1");
        } else {
            header("Location: reservas.php?creada=1");
        }

        exit;
    }
}

// Mensaje de confirmación
if (isset($_GET["creada"])) {
    $mensaje = "¡La reserva fue creada correctamente!";
}

// Consultar las reservas del usuario correspondiente
$consulta = $conexion->prepare(
    "SELECT * FROM reservas
     WHERE usuario_id = ?
     ORDER BY creado_en DESC"
);

$consulta->execute([$usuarioReservaId]);
$reservas = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reservas - AeroTraslados</title>

    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

<main>

    <h1>AeroTraslados</h1>

    <?php if ($modoAdministrador): ?>

        <h2>Solicitar un traslado para
            <?php
            echo htmlspecialchars(
                $usuarioReserva["nombre"] . " " . $usuarioReserva["apellido"]
            );
            ?>
        </h2>

        <p>Estás creando una reserva para el usuario seleccionado.</p>

    <?php else: ?>

        <h2>Solicitar un traslado</h2>

    <?php endif; ?>

    <?php if ($mensaje !== ""): ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="reservas.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_reservas"]); ?>"
        >

        <label>Fecha del traslado</label>
        <input type="date" name="fecha" required>

        <label>Hora del traslado</label>
        <input type="time" name="hora" required>

        <label>Origen</label>
        <input type="text" name="origen" required>

        <label>Destino</label>
        <input type="text" name="destino" required>

        <label>Cantidad de pasajeros</label>
        <input type="number" name="pasajeros" min="1" max="20" required>

        <label>Observaciones (opcional)</label>
        <textarea name="observaciones"></textarea>

        <button type="submit">Solicitar reserva</button>

    </form>

    <h2>Reservas anteriores</h2>

    <?php if (count($reservas) > 0): ?>

        <?php foreach ($reservas as $reserva): ?>

            <section>

                <p><strong>Fecha:</strong>
                    <?php echo htmlspecialchars($reserva["fecha_traslado"]); ?>
                </p>

                <p><strong>Hora:</strong>
                    <?php echo htmlspecialchars(substr($reserva["hora_traslado"], 0, 5)); ?>
                </p>

                <p><strong>Origen:</strong>
                    <?php echo htmlspecialchars($reserva["origen"]); ?>
                </p>

                <p><strong>Destino:</strong>
                    <?php echo htmlspecialchars($reserva["destino"]); ?>
                </p>

                <p><strong>Pasajeros:</strong>
                    <?php echo (int) $reserva["pasajeros"]; ?>
                </p>

                <p><strong>Estado:</strong>
                    <?php echo htmlspecialchars($reserva["estado"]); ?>
                </p>

                <?php if (
                    !$modoAdministrador &&
                    in_array($reserva["estado"], ["pendiente", "confirmada"], true)
                ): ?>

                    <form action="cancelar_reserva.php" method="POST">

                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo (int) $reserva["id"]; ?>"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION["csrf_reservas"]); ?>"
                        >

                        <button type="submit">Cancelar reserva</button>

                    </form>

                <?php endif; ?>

            </section>

            <hr>

        <?php endforeach; ?>

    <?php else: ?>

        <p>Todavía no hay reservas.</p>

    <?php endif; ?>

    <br>

    <?php if ($modoAdministrador): ?>

        <a href="reservas.php?salir_admin=1">
            Volver al panel de administración
        </a>

    <?php else: ?>

        <a href="panel.php">Volver a mi cuenta</a>

        <br><br>

        <a href="cerrar_sesion.php">Cerrar sesión</a>

    <?php endif; ?>

</main>

</body>
</html>
