<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tta_repuestos");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Variables para búsqueda
$buscando_boleta = isset($_GET['no_boleta']) ? intval($_GET['no_boleta']) : null;
$buscando_fecha = isset($_GET['fecha_filtro']) ?$_GET['fecha_filtro'] : null;

$boleta_data = null;
$detalles_data = [];$lista_por_fecha = [];

// 1. Si se busca por número exacto de boleta
if ($buscando_boleta) {
    $stmt =$conexion->prepare("SELECT * FROM boletas WHERE no_boleta = ?");
    $stmt->bind_param("i", $buscando_boleta);$stmt->execute();
    $res_boleta =$stmt->get_result();

    if ($res_boleta->num_rows > 0) {
        $boleta_data =$res_boleta->fetch_assoc();
        
        // Buscar los detalles de esa boleta
        $stmt_det =$conexion->prepare("SELECT * FROM detalles_boleta WHERE boleta_id = ?");
        $stmt_det->bind_param("i", $boleta_data['id']);$stmt_det->execute();
        $res_det =$stmt_det->get_result();
        
        while ($row_det =$res_det->fetch_assoc()) {
            $detalles_data[] =$row_det;
        }
        $stmt_det->close();
    } else {
        echo "<script>alert('La boleta No. $buscando_boleta no fue encontrada.');</script>";
    }
    $stmt->close();
}

// 2. Si se busca por fecha (filtra todas las boletas de ese día)
if ($buscando_fecha) {
    $stmt_f =$conexion->prepare("SELECT * FROM boletas WHERE fecha = ? ORDER BY no_boleta DESC");
    $stmt_f->bind_param("s", $buscando_fecha);$stmt_f->execute();
    $res_f =$stmt_f->get_result();
    
    while ($row_f =$res_f->fetch_assoc()) {
        $lista_por_fecha[] =$row_f;
    }
    $stmt_f->close();
}

// Configurar valores para el formulario (si se encontró una boleta específica)
if ($boleta_data) {
    $siguiente_boleta =$boleta_data['no_boleta'];
    $lugar_val =$boleta_data['lugar'];
    $direccion_val =$boleta_data['direccion'];
    $observaciones_val =$boleta_data['observaciones'];
    $operador_val =$boleta_data['operador'];
    $autorizacion_val =$boleta_data['autorizacion'];

    $partes_fecha = explode('-',$boleta_data['fecha']);
    $anio_actual =$partes_fecha[0];
    $mes_num =$partes_fecha[1];
    $dia_actual =$partes_fecha[2];

    $meses_inv = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];$mes_actual = $meses_inv[$mes_num] ?? 'Enero';
} else {
    // Lógica para el correlativo: arranca en 4050 si no hay mayores registradas
    $sql_ultimo = "SELECT MAX(no_boleta) as ultimo FROM boletas";
    $resultado =$conexion->query($sql_ultimo);$fila = $resultado->fetch_assoc();$ultimo = $fila['ultimo'] ? intval($fila['ultimo']) : 0;
    
    $siguiente_boleta = ($ultimo >= 4050) ?$ultimo + 1 : 4050;

    $lugar_val = "Guatemala";
    $direccion_val = "Km 21.5 Antigua Carretera a Amatitlán";
    $observaciones_val = "";
    $operador_val = "";
    $autorizacion_val = "";
    $dia_actual = date('d');
    $meses = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];$mes_actual = $meses[date('m')];$anio_actual = date('Y');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Solicitud de Consumibles - TTA</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-bar-container {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }
        .search-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .results-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .results-box ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Barra de Búsqueda -->
    <div class="search-bar-container">
        <div class="search-group">
            <label style="font-weight: bold; font-size: 13px;">No. Boleta:</label>
            <input type="number" id="inputBuscarNo" placeholder="Ej. 4050" style="padding: 5px; width: 90px;">
            <button type="button" onclick="buscarPorNumero()" style="background: #007bff; color: white; padding: 6px 10px; border: none; cursor: pointer; border-radius: 4px;">Ir</button>
        </div>

        <div class="search-group">
            <label style="font-weight: bold; font-size: 13px;">Fecha:</label>
            <input type="date" id="inputBuscarFecha" value="<?php echo $buscando_fecha; ?>" style="padding: 5px;">
            <button type="button" onclick="buscarPorFecha()" style="background: #28a745; color: white; padding: 6px 10px; border: none; cursor: pointer; border-radius: 4px;">Buscar por Fecha</button>
        </div>

        <div>
            <a href="index.php" style="background: #6c757d; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">+ Nueva Boleta</a>
        </div>
    </div>

    <!-- Resultados por Fecha -->
    <?php if ($buscando_fecha): ?>
        <div class="results-box">
            <strong>Boletas encontradas para la fecha <?php echo $buscando_fecha; ?>:</strong>
            <?php if (!empty($lista_por_fecha)): ?>
                <ul>
                    <?php foreach ($lista_por_fecha as$b): ?>
                        <li>
                            Boleta No. <strong><?php echo $b['no_boleta']; ?></strong> — Operador: <?php echo htmlspecialchars($b['operador'] ?: 'Sin registrar'); ?> 
                            <a href="index.php?no_boleta=<?php echo $b['no_boleta']; ?>" style="color: #0056b3; font-weight: bold; margin-left: 10px;">[Cargar esta boleta]</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="margin: 5px 0 0 0; color: #856404;">No se encontraron boletas en esta fecha.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form id="solicitudForm" action="guardar.php" method="POST">
        <!-- Encabezado -->
        <div class="header">
            <div class="logo-section">
                <div class="logo-box">TTA</div>
                <div class="logo-text">
                    <span>Tecnología</span>
                    <span>Textil</span>
                    <span>Avanzada</span>
                </div>
            </div>

            <div class="company-title">
                <h2>Control de Solicitud de Consumibles, Materia Prima y Repuestos.</h2>
                <h3>Tecnología Textil Avanzada, S.A.</h3>
            </div>

            <div class="ticket-box">
                <div class="title">Boleta de Solicitud</div>
                <div class="number">
                    <input type="text" id="noBoleta" name="noBoleta" value="<?php echo $siguiente_boleta; ?>" readonly required>
                </div>
            </div>
        </div>

        <!-- Datos de Ubicación y Fecha -->
        <div class="form-row">
            <div class="form-group flex-grow">
                <label>Lugar:</label>
                <input type="text" id="lugar" name="lugar" value="<?php echo htmlspecialchars($lugar_val); ?>">
            </div>

            <div class="form-group date-inputs">
                <input type="number" id="dia" name="dia" value="<?php echo $dia_actual; ?>" placeholder="Día" required>
                <label>de</label>
                <input type="text" id="mes" name="mes" value="<?php echo $mes_actual; ?>" placeholder="Mes" required>
                <label>de</label>
                <input type="number" id="anio" name="anio" value="<?php echo $anio_actual; ?>" placeholder="Año" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group width-full">
                <label>Dirección:</label>
                <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($direccion_val); ?>">
            </div>
        </div>

        <!-- Tabla de Productos (Orden: Unidades, Código, Descripción) -->
        <table id="itemsTable">
            <thead>
                <tr>
                    <th class="col-units" style="width: 15%;">Unidades</th>
                    <th style="width: 25%;">Código</th>
                    <th class="col-desc">Descripción del producto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($detalles_data)): ?>
                    <?php foreach ($detalles_data as $detalle): ?>
                        <tr>
                            <td><input type="text" name="unidades[]" value="<?php echo htmlspecialchars($detalle['unidades']); ?>"></td>
                            <td><input type="text" name="codigo[]" value="<?php echo htmlspecialchars($detalle['codigo'] ?? ''); ?>"></td>
                            <td><input type="text" name="descripcion[]" value="<?php echo htmlspecialchars($detalle['descripcion']); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for($i=0; $i<5; $i++): ?>
                        <tr>
                            <td><input type="text" name="unidades[]"></td>
                            <td><input type="text" name="codigo[]"></td>
                            <td><input type="text" name="descripcion[]"></td>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Observaciones y Firmas -->
        <div class="footer-section">
            <div class="field-line">
                <label>Observaciones:</label>
                <input type="text" id="observaciones" name="observaciones" value="<?php echo htmlspecialchars($observaciones_val); ?>">
            </div>
            <div class="field-line">
                <label>Operador:</label>
                <input type="text" id="operador" name="operador" value="<?php echo htmlspecialchars($operador_val); ?>">
            </div>
            <div class="field-line">
                <label>Autorización:</label>
                <input type="text" id="autorizacion" name="autorizacion" value="<?php echo htmlspecialchars($autorizacion_val); ?>">
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="actions">
            <button type="button" class="btn btn-add" id="btnAddRow">+ Agregar Fila</button>
            <button type="reset" class="btn btn-reset" id="btnReset">Limpiar</button>
            <button type="button" class="btn btn-print" id="btnPrint" onclick="window.print();">Imprimir</button>
            <button type="submit" class="btn btn-save" id="btn-save">Guardar</button>
        </div>
    </form>
</div>

<script>
    function buscarPorNumero() {
        const numBoleta = document.getElementById('inputBuscarNo').value;
        if (numBoleta) {
            window.location.href = 'index.php?no_boleta=' + numBoleta;
        } else {
            alert('Ingrese un número de boleta.');
        }
    }

    function buscarPorFecha() {
        const fecha = document.getElementById('inputBuscarFecha').value;
        if (fecha) {
            window.location.href = 'index.php?fecha_filtro=' + fecha;
        } else {
            alert('Seleccione una fecha.');
        }
    }

    // Actualizado también el botón "+ Agregar Fila" para que incluya el input de código dinámicamente si usas app.js
    document.getElementById('btnAddRow').addEventListener('click', function() {
        const tableBody = document.querySelector('#itemsTable tbody');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td><input type="text" name="codigo[]"></td>
            <td><input type="text" name="unidades[]"></td>
            <td><input type="text" name="descripcion[]"></td>
        `;
        
        tableBody.appendChild(newRow);
    });
</script>
</body>
</html>