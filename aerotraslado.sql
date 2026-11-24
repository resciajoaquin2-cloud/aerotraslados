CREATE DATABASE IF NOT EXISTS aerotraslados
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE aerotraslados;


-- TABLA DE USUARIOS

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('usuario', 'admin') DEFAULT 'usuario',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    archivado_en DATETIME NULL
);


-- TABLA DE RESERVAS

CREATE TABLE IF NOT EXISTS reservas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    fecha_traslado DATE NOT NULL,
    hora_traslado TIME NOT NULL,
    origen VARCHAR(200) NOT NULL,
    destino VARCHAR(200) NOT NULL,
    pasajeros TINYINT UNSIGNED NOT NULL,
    observaciones VARCHAR(500) NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada')
        DEFAULT 'pendiente',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);