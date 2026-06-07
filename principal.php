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
    $email_alertas = mysqli_real_escape_string($conn, $_POST['email_alertas']);

    $sql_update = "UPDATE usuarios SET umbral_humedad = $umbral_humedad, umbral_agua = $umbral_agua, email_alertas = '$email_alertas' WHERE usuario = '$usuario_actual'";
    
    if(mysqli_query($conn, $sql_update)){
        $mensaje_exito = "¡Configuración de alertas actualizada!";
    }
}

$consulta = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario_actual'");
$datos_user = mysqli_fetch_assoc($consulta);

$distancia_inicial = (int)($datos_user['acceso_actual'] ?? 100); 
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
            <h3 style="color: #4ade80; margin-bottom: 15px;">⚙️ Configurar Alertas por Correo</h3>
            <?php if(!empty($mensaje_exito)) { echo "<p class='exito'>$mensaje_exito</p>"; } ?>
            
            <form method="POST" action="principal.php">
                <label style="color: #a1a1aa; font-size: 14px;">Umbral mínimo Humedad Suelo (%):</label>
                <input type="number" name="umbral_humedad" value="<?php echo htmlspecialchars($datos_user['umbral_humedad'] ?? '30'); ?>" required>
                
                <label style="color: #a1a1aa; font-size: 14px;">Umbral mínimo Nivel Piscina (%):</label>
                <input type="number" name="umbral_agua" value="<?php echo htmlspecialchars($datos_user['umbral_agua'] ?? '30'); ?>" required>
                
                <label style="color: #a1a1aa; font-size: 14px;">Correo para recibir alertas:</label>
                <input type="email" name="email_alertas" placeholder="ejemplo@correo.com" value="<?php echo htmlspecialchars($datos_user['email_alertas'] ?? ''); ?>">
                
                <button type="submit" name="actualizar_umbrales" style="margin-top: 10px;">Guardar Configuración</button>
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
                    }
                })
                .catch(error => console.log('Error actualizando sensores:', error));
        }

        setInterval(actualizarSensores, 2000);
    </script>
</body>
</html>