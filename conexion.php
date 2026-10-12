<?php
$servidor = "localhost";
$usuario = "root";
$pass = "";
$base = "solicitudes_db";

$conexion = new mysqli($servidor, $usuario, $pass, $base);

if ($conexion->connect_error) {
    die("Error conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");
?>