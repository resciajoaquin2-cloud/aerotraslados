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
    header("Location: usuarios_archivados.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: usuarios_archivados.php");
    exit;
}

// Crear token CSRF
if (!isset($_SESSION["csrf_reactivar"])) {
    $_SESSION["csrf_reactivar"] = bin2hex(random_bytes(32));
}

$token = $_POST["csrf_token"] ?? "";

if (!hash_equals($_SESSION["csrf_reactivar"], $token)) {
    die("Solicitud no válida.");
}

// Reactivar al usuario
$consulta = $conexion->prepare(
    "UPDATE usuarios
     SET archivado_en = NULL
     WHERE id = ? AND archivado_en IS NOT NULL"
);

$consulta->execute([$id]);

unset($_SESSION["csrf_reactivar"]);

header("Location: usuarios_archivados.php");
exit;
?>