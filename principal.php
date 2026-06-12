<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario_actual = $_SESSION['usuario'];
$mensaje_exito = "";

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

$distancia_inicial = (int)($datos_user['acceso_actual'] ?? 100); 
// Obtener el historial de lecturas (últimos 10 registros)
$query_historial = mysqli_query($conn, "SELECT humedad, agua, fecha FROM historial_lecturas WHERE usuario = '$usuario_actual' ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Finca Inteligente</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .card {
            background: #1e1e24; 
            flex: 1; 
            text-align: center; 
            min-width: 200px; 
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        @keyframes pulso-rojo {
            0% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.5); border-color: rgba(248, 113, 113, 0.6); }
            70% { box-shadow: 0 0 0 10px rgba(248, 113, 113, 0); border-color: rgba(248, 113, 113, 1); }
            100% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0); border-color: rgba(248, 113, 113, 0.6); }
        }
        .card-ocupada-anim {
            animation: pulso-rojo 1.5s infinite;
        }
    </style>
</head>
<body>
    <div class="welcome-container" style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>🌱 Panel de Control</h1>
        <p>Bienvenido al sistema de monitoreo, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>.</p>
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
            
            <div id="tarjeta_acceso" class="card" style="border: 2px solid #4ade80; width: 100%;">
                <h3 id="titulo_acceso" style="color: #4ade80; margin-bottom: 5px;">🟢 ACCESO LIBRE</h3>
                <h1 id="estado_acceso" style="font-size: 2.2rem; margin: 10px 0; color: white;">DESPEJADO</h1>
                <p id="distancia_real" style="color: #a1a1aa; font-size: 14px;">Distancia detectada: <?php echo $distancia_inicial; ?> cm</p>
            </div>

            <div class="card">
                <h3 style="color: #38bdf8; margin-bottom: 5px;">💧 Nivel de Piscina</h3>
                <h1 id="valor_agua" style="font-size: 3.5rem; margin: 10px 0; color: white;">
                    <?php echo htmlspecialchars($datos_user['agua_actual'] ?? '0'); ?>%
                </h1>
                <p style="color: #a1a1aa; font-size: 14px;">Umbral de alerta: <?php echo htmlspecialchars($datos_user['umbral_agua'] ?? '30'); ?>%</p>
            </div>

            <div class="card">
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

        <div class="card" style="background: #1e1e24; margin-bottom: 20px; overflow-x: auto;">
            <h3 style="color: #a78bfa; margin-bottom: 15px;">📊 Historial de Lecturas</h3>
            <table style="width: 100%; border-collapse: collapse; text-align: center; color: white; font-size: 14px;">
                <thead>
                    <tr style="border-bottom: 1px solid #3f3f46;">
                        <th style="padding: 10px;">Fecha y Hora</th>
                        <th style="padding: 10px;">Humedad (%)</th>
                        <th style="padding: 10px;">Piscina (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($query_historial && mysqli_num_rows($query_historial) > 0) {
                        while ($fila = mysqli_fetch_assoc($query_historial)) {
                            echo "<tr style='border-bottom: 1px solid #3f3f46;'>";
                            echo "<td style='padding: 10px; color: #a1a1aa;'>" . htmlspecialchars($fila['fecha']) . "</td>";
                            echo "<td style='padding: 10px; color: #fbbf24;'>" . htmlspecialchars($fila['humedad']) . "%</td>";
                            echo "<td style='padding: 10px; color: #60a5fa;'>" . htmlspecialchars($fila['agua']) . "%</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='padding: 15px; color: #a1a1aa;'>Aún no hay registros en el historial.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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
                        
                        let distancia = parseInt(datos.acceso_actual) || 100;
                        
                        let tarjeta = document.getElementById('tarjeta_acceso');
                        let tituloAcceso = document.getElementById('titulo_acceso');
                        let textoEstado = document.getElementById('estado_acceso');
                        let textoDistancia = document.getElementById('distancia_real');

                        textoDistancia.innerText = "Distancia detectada: " + distancia + " cm";

                        if (distancia <= 10) {
                            tituloAcceso.innerText = "🚗 ZONA OCUPADA";
                            tituloAcceso.style.color = "#f87171";
                            textoEstado.innerText = "ESPERA / RFID";
                            textoEstado.style.color = "#ffffff";
                            tarjeta.style.borderColor = "#f87171";
                            tarjeta.classList.add("card-ocupada-anim");
                        } 
                        else if (distancia > 10 && distancia <= 15) {
                            tituloAcceso.innerText = "⚠️ PRECAUCIÓN";
                            tituloAcceso.style.color = "#fbbf24";
                            textoEstado.innerText = "ACERCÁNDOSE";
                            textoEstado.style.color = "#ffffff";
                            tarjeta.style.borderColor = "#fbbf24";
                            tarjeta.classList.remove("card-ocupada-anim");
                        } 
                        else {
                            tituloAcceso.innerText = "🟢 ACCESO LIBRE";
                            tituloAcceso.style.color = "#4ade80";
                            textoEstado.innerText = "DESPEJADO";
                            textoEstado.style.color = "#ffffff";
                            tarjeta.style.borderColor = "#4ade80";
                            tarjeta.classList.remove("card-ocupada-anim");
                        }
                        // Recargar la página silenciosamente cada 30 segundos para actualizar la tabla del historial
                        // sin interrumpir la experiencia de los grandes números en tiempo real
                    }
                })
                .catch(error => console.log('Error actualizando sensores:', error));
        }

        setInterval(actualizarSensores, 3000);
        
        // Refresca la tabla del historial cada 1 minuto (60000ms)
        setInterval(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>