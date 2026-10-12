<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include 'conexion.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
</head>
<body>
    <h2>Productos registrados</h2>

    <a href="boleta.html">Nueva solicitud</a>
    <a href="cerrar.php">Cerrar sesión</a>

    <?php
    $sql = "SELECT s.id, s.no_boleta, s.lugar, s.direccion, d.unidades, d.descripcion
            FROM solicitudes s
            INNER JOIN solicitudes_detalle d ON d.solicitud_id = s.id
            ORDER BY s.id DESC";

    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
            echo "<strong>Boleta:</strong> " . $fila['no_boleta'] . "<br>";
            echo "<strong>Lugar:</strong> " . $fila['lugar'] . "<br>";
            echo "<strong>Dirección:</strong> " . $fila['direccion'] . "<br>";
            echo "<strong>Unidades:</strong> " . $fila['unidades'] . "<br>";
            echo "<strong>Descripción:</strong> " . $fila['descripcion'] . "<br>";
            echo "</div>";
        }
    } else {
        echo "<p>No hay productos registrados.</p>";
    }
    ?>

</body>
</html>