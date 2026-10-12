<?php
require_once __DIR__ . "/conexion.php";

$nombre = "admin";
$contrasena = "123456";
$rol = "admin";

$contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios (nombre, contrasena, rol)
        VALUES (?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sss", $nombre, $contrasena_hash, $rol);

if ($stmt->execute()) {
    echo "Usuario creado correctamente.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conexion->close();
?>