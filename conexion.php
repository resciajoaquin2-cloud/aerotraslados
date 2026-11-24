<?php

$host = "localhost";
$basedatos = "aerotraslados";
$usuario = "root";
$password = "";

try {

    $conexion = new PDO(
        "mysql:host=$host;dbname=$basedatos;charset=utf8mb4",
        $usuario,
        $password
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Error al conectar con la base de datos.");

}

?>