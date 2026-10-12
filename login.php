<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header("Location: lista.php");
    exit;
}

include 'conexion.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if ($nombre === "" || $contrasena === "") {
        $error = "Debes completar todos los campos.";
    } else {
        $stmt = $conexion->prepare("SELECT id, nombre, rol, contrasena FROM usuarios WHERE nombre = ? LIMIT 1");

        if (!$stmt) {
            $error = "Error en la consulta: " . $conexion->error;
        } else {
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {
                $usuario = $resultado->fetch_assoc();

                if ($contrasena === $usuario['contrasena']) {
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nombre'] = $usuario['nombre'];
                    $_SESSION['rol'] = $usuario['rol'];

                    header("Location: lista.php");
                    exit;
                } else {
                    $error = "❌ Contraseña incorrecta";
                }
            } else {
                $error = "❌ Usuario no encontrado";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - TTA</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 5px;
        }

        .logo-sub {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 20px;
        }

        .campo {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #27ae60;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #219653;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">TTA</div>
        <div class="logo-sub">Tecnología Textil Avanzada</div>
        <h2>Iniciar Sesión</h2>

        <?php if ($error !== ""): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="campo">
                <label>Usuario:</label>
                <input type="text" name="nombre" required>
            </div>

            <div class="campo">
                <label>Contraseña:</label>
                <input type="password" name="contrasena" required>
            </div>

            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>