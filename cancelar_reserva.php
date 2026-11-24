<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: reservas.php");
    exit;
}

// Verificar token CSRF
$token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_reservas"]) ||
    !hash_equals($_SESSION["csrf_reservas"], $token)
) {
    die("Solicitud no válida.");
}

// Validar ID de la reserva
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: reservas.php");
    exit;
}

// Cancelar únicamente una reserva del usuario
$consulta = $conexion->prepare(
    "UPDATE reservas
     SET estado = 'cancelada'
     WHERE id = ?
     AND usuario_id = ?
     AND estado IN ('pendiente', 'confirmada')"
);

$consulta->execute([
    $id,
    $_SESSION["usuario_id"]
]);

header("Location: reservas.php");
exit;
?>