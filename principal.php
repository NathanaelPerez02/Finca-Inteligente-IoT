<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario_actual = $_SESSION['usuario'];
$mensaje_exito = "";

// Lógica para guardar la configuración cuando se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_umbrales'])) {
    $umbral_humedad = (int)$_POST['umbral_humedad'];
    $umbral_agua = (int)$_POST['umbral_agua'];

    $sql_update = "UPDATE usuarios SET umbral_humedad = $umbral_humedad, umbral_agua = $umbral_agua WHERE usuario = '$usuario_actual'";
    
    if(mysqli_query($conn, $sql_update)){
        $mensaje_exito = "¡Configuración de niveles actualizada!";
    }
}

// Cargar los datos actuales del usuario
$consulta = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario_actual'");
$datos_user = mysqli_fetch_assoc($consulta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Finca Inteligente</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="welcome-container" style="max-width: 600px;">
        <h1>🌱 Panel de Control</h1>
        <p>Bienvenido al sistema de monitoreo, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>.</p>
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
            <div class="card" style="background: #1e1e24; flex: 1; text-align: center; min-width: 200px;">
                <h3 style="color: #60a5fa; margin-bottom: 5px;">💧 Nivel de Piscina</h3>
                <h1 id="valor_agua" style="font-size: 3.5rem; margin: 10px 0; color: white;">
                    <?php echo htmlspecialchars($datos_user['agua_actual'] ?? '0'); ?>%
                </h1>
                <p style="color: #a1a1aa; font-size: 14px;">Umbral de alerta: <?php echo htmlspecialchars($datos_user['umbral_agua'] ?? '30'); ?>%</p>
            </div>

            <div class="card" style="background: #1e1e24; flex: 1; text-align: center; min-width: 200px;">
                <h3 style="color: #fbbf24; margin-bottom: 5px;">🪴 Humedad de Suelo</h3>
                <h1 id="valor_humedad" style="font-size: 3.5rem; margin: 10px 0; color: white;">
                    <?php echo htmlspecialchars($datos_user['humedad_actual'] ?? '0'); ?>%
                </h1>
                <p style="color: #a1a1aa; font-size: 14px;">Umbral de alerta: <?php echo htmlspecialchars($datos_user['umbral_humedad'] ?? '30'); ?>%</p>
            </div>
        </div>

        <div class="card" style="background: #1e1e24; margin: 20px 0; max-width: 100%; text-align: left;">
            <h3 style="color: #4ade80; margin-bottom: 15px;">⚙️ Configurar Niveles de Alerta</h3>
            <?php if(!empty($mensaje_exito)) { echo "<p class='exito' style='color: white; background-color: #059669; padding: 10px; border-radius: 5px; font-weight: bold;'>$mensaje_exito</p>"; } ?>
            
            <form method="POST" action="principal.php">
                <label style="color: #a1a1aa; font-size: 14px;">Umbral mínimo Humedad Suelo (%):</label>
                <input type="number" name="umbral_humedad" value="<?php echo htmlspecialchars($datos_user['umbral_humedad'] ?? '30'); ?>" required>
                
                <label style="color: #a1a1aa; font-size: 14px;">Umbral mínimo Nivel Piscina (%):</label>
                <input type="number" name="umbral_agua" value="<?php echo htmlspecialchars($datos_user['umbral_agua'] ?? '30'); ?>" required>
                
                <button type="submit" name="actualizar_umbrales" style="margin-top: 10px;">Guardar Configuración</button>
            </form>
        </div>

        <div class="card" style="background: #1e1e24; margin-bottom: 20px;">
            <h3 style="color: #c084fc; margin-bottom: 10px;">🕹️ Simulador de Hardware</h3>
            <p style="color: #a1a1aa; font-size: 14px; margin-bottom: 15px;">Usa este panel para simular los datos que enviaría la placa física.</p>
            
            <form action="api_sensores.php" target="_blank" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($datos_user['usuario'] ?? 'oldtote'); ?>">
                
                <input type="number" name="agua" placeholder="Nivel Piscina (%)" required style="flex: 1; min-width: 120px; padding: 10px; border-radius: 5px; border: 1px solid #3f3f46; background: #27272a; color: white;">
                
                <input type="number" name="humedad" placeholder="Humedad Suelo (%)" required style="flex: 1; min-width: 120px; padding: 10px; border-radius: 5px; border: 1px solid #3f3f46; background: #27272a; color: white;">
                
                <button type="submit" style="background: #c084fc; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Simular Envío</button>
            </form>
        </div>

        <div class="button-group">
            <a href="logout.php" class="btn" style="background:#f87171; color:white;">Cerrar Sesión</a>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        function actualizarSensores() {
            fetch('get_datos.php')
                .then(respuesta => respuesta.json())
                .then(datos => {
                    if(!datos.error) {
                        document.getElementById('valor_agua').innerText = datos.agua_actual + '%';
                        document.getElementById('valor_humedad').innerText = datos.humedad_actual + '%';
                    }
                })
                .catch(error => console.log('Error actualizando:', error));
        }

        setInterval(actualizarSensores, 3000);
    </script>
</body>
</html>