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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_reservas.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$estado = $_POST["estado"] ?? "";
$token = $_POST["csrf_token"] ?? "";

if (!$id || !in_array($estado, ["pendiente", "confirmada", "cancelada", "completada"], true)) {
    header("Location: admin_reservas.php");
    exit;
}

// Verificar token CSRF
if (
    !isset($_SESSION["csrf_reservas"]) ||
    !hash_equals($_SESSION["csrf_reservas"], $token)
) {
    die("Solicitud no válida.");
}

// Actualizar estado de la reserva
$consulta = $conexion->prepare(
    "UPDATE reservas
     SET estado = ?
     WHERE id = ?"
);

$consulta->execute([$estado, $id]);

header("Location: admin_reservas.php");
exit;
?>