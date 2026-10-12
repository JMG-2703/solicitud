<?php
// Configuración de conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tta_repuestos";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Comprobar si los datos se enviaron por método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Capturar los datos generales del formulario de la boleta
    $noBoleta = $_POST['noBoleta'];
    $lugar = $_POST['lugar'];
    $dia = $_POST['dia'];
    $mesTexto = strtolower(trim($_POST['mes']));
    $anio = $_POST['anio'];
    $direccion = $_POST['direccion'];
    $observaciones = $_POST['observaciones'];
    $operador = $_POST['operador'];
    $autorizacion = $_POST['autorizacion'];

    // Convertir el nombre del mes ingresado a su número correspondiente (para tipo DATE en MySQL)
    $meses_num = [
        'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
        'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
        'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12'
    ];
    
    $mesNumero = $meses_num[$mesTexto] ?? date('m'); // Si escriben mal el mes, usa el mes actual por defecto
    $fecha_formateada = "$anio-$mesNumero-$dia";

    // 2. Capturar los arreglos de la tabla de productos (incluyendo código, unidades y descripción)
    $codigos = $_POST['codigo'] ?? [];
    $unidades = $_POST['unidades'] ?? [];
    $descripciones = $_POST['descripcion'] ?? [];

    // Iniciar una transacción SQL (garantiza integridad: si algo falla, no se guarda a medias)
    $conn->begin_transaction();

    try {
        // A. Insertar o actualizar la cabecera (La boleta)
        // Usamos INSERT ... ON DUPLICATE KEY UPDATE por si estás reeditando una boleta existente
        $sql_boleta = "INSERT INTO boletas (no_boleta, lugar, fecha, direccion, observaciones, operador, autorizacion) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE 
                       lugar = VALUES(lugar), 
                       fecha = VALUES(fecha), 
                       direccion = VALUES(direccion), 
                       observaciones = VALUES(observaciones), 
                       operador = VALUES(operador), 
                       autorizacion = VALUES(autorizacion)";
        
        $stmt = $conn->prepare($sql_boleta);
        $stmt->bind_param("issssss", $noBoleta, $lugar, $fecha_formateada, $direccion, $observaciones, $operador, $autorizacion);
        $stmt->execute();
        
        // Obtener el ID de la boleta (ya sea el nuevo autoincrementable o el ID de la boleta existente)
        $boleta_id = $stmt->insert_id;
        if ($boleta_id == 0) {
            // Si era una actualización (DUPLICATE KEY), insert_id devuelve 0, por lo que buscamos su ID real
            $stmt_id = $conn->prepare("SELECT id FROM boletas WHERE no_boleta = ?");
            $stmt_id->bind_param("i", $noBoleta);
            $stmt_id->execute();
            $res_id = $stmt_id->get_result();
            if ($row_id = $res_id->fetch_assoc()) {
                $boleta_id = $row_id['id'];
            }
            $stmt_id->close();
            
            // Si editamos una boleta anterior, limpiamos sus detalles viejos para volver a insertar la lista actualizada
            $stmt_del = $conn->prepare("DELETE FROM detalles_boleta WHERE boleta_id = ?");
            $stmt_del->bind_param("i", $boleta_id);
            $stmt_del->execute();
            $stmt_del->close();
        }
        $stmt->close();

        // B. Recorrer y guardar los productos/repuestos asociados en la tabla de detalles
        $sql_detalle = "INSERT INTO detalles_boleta (boleta_id, codigo, unidades, descripcion) VALUES (?, ?, ?, ?)";
        $stmt_detalle = $conn->prepare($sql_detalle);

        for ($i = 0; $i < count($descripciones); $i++) {
            // Solo guardamos la fila si la descripción del producto NO está vacía
            if (!empty(trim($descripciones[$i]))) {
                $cod = $codigos[$i] ?? '';
                $uni = $unidades[$i] ?? '';
                $desc = $descripciones[$i];
                
                $stmt_detalle->bind_param("isss", $boleta_id, $cod, $uni, $desc);
                $stmt_detalle->execute();
            }
        }
        $stmt_detalle->close();

        // Confirmar la transacción exitosa
        $conn->commit();
        
        echo "<script>
                alert('¡Boleta guardada con éxito!'); 
                window.location.href='index.php';
              </script>";

    } catch (Exception $e) {
        // Revertir cambios si ocurre algún error
        $conn->rollback();
        echo "<script>
                alert('Error al guardar la boleta: " . addslashes($e->getMessage()) . "'); 
                window.history.back();
              </script>";
    }

    $conn->close();
}
?>