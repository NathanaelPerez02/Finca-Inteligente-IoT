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
        /* Estilos para los Modales */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-content {
            background: #1e1e24; padding: 25px; border-radius: 10px; border: 1px solid #3f3f46;
            width: 90%; max-width: 400px; text-align: left; color: white;
        }
        .modal-input {
            width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #3f3f46;
            background: #27272a; color: white; box-sizing: border-box;
        }
        .btn-cerrar { background: transparent; border: none; color: #a1a1aa; float: right; font-size: 1.5rem; cursor: pointer; }
        .btn-accion { width: 100%; padding: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px;}
    </style>
</head>
<body>
    <div class="welcome-container" style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>🌱 Panel de Control</h1>
        <p>Bienvenido al sistema de monitoreo, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>.</p>
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
            
            <div id="tarjeta_modo" class="card" style="border: 2px solid #a78bfa; width: 100%;">
                <h3 style="color: #a78bfa; margin-bottom: 5px;">MODO DEL SISTEMA</h3>
                <h1 id="estado_modo" style="font-size: 2.2rem; margin: 10px 0; color: white;">CARGANDO...</h1>
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                    <button onclick="ejecutarAccion('cambiar_modo', 0)" style="background: #4ade80; color: #1e1e24; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer; font-weight: bold;">AUTO</button>
                    <button onclick="ejecutarAccion('cambiar_modo', 1)" style="background: #fbbf24; color: #1e1e24; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer; font-weight: bold;">MANUAL</button>
                </div>
            </div>

            <div id="tarjeta_acceso" class="card" style="border: 2px solid #4ade80; width: 100%;">
                <h3 id="titulo_acceso" style="color: #4ade80; margin-bottom: 5px;">ESTADO DEL PORTÓN</h3>
                <h1 id="estado_puerta" style="font-size: 2.2rem; margin: 10px 0; color: white;">CERRADO</h1>
                <p id="distancia_real" style="color: #a1a1aa; font-size: 14px;">Distancia detectada: -- cm</p>
            </div>

            <div class="card">
                <h3 style="color: #38bdf8; margin-bottom: 5px;">Nivel de Piscina</h3>
                <h1 id="valor_agua" style="font-size: 3.5rem; margin: 10px 0; color: white;">
                    <?php echo htmlspecialchars($datos_user['agua_actual'] ?? '0'); ?>%
                </h1>
            </div>

            <div class="card">
                <h3 style="color: #fbbf24; margin-bottom: 5px;">Humedad Suelo</h3>
                <h1 id="valor_humedad" style="font-size: 3.5rem; margin: 10px 0; color: white;">
                    <?php echo htmlspecialchars($datos_user['humedad_actual'] ?? '0'); ?>%
                </h1>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <button onclick="abrirModal('modalAbrir')" style="flex: 1; padding: 15px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                Apertura Remota
            </button>
            <button onclick="abrirModal('modalTarjeta')" style="flex: 1; padding: 15px; background: #8b5cf6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                Registrar Nueva Tarjeta
            </button>
        </div>

        <div class="card" style="background: #1e1e24; margin: 20px 0; max-width: 100%; text-align: left;">
            <h3 style="color: #4ade80; margin-bottom: 15px;">Configurar Niveles de Alerta</h3>
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
            <h3 style="color: #a78bfa; margin-bottom: 15px;">Historial de Lecturas</h3>
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
            <h3 style="color: #c084fc; margin-bottom: 10px;">Simulador de Hardware</h3>
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

    <div id="modalAbrir" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-cerrar" onclick="cerrarModal('modalAbrir')">&times;</button>
            <h3 style="color: #3b82f6; margin-top: 0;">Confirmar Apertura</h3>
            <p style="color: #a1a1aa; font-size: 14px;">Ingrese su contraseña maestra para abrir la tranquera remotamente.</p>
            <input type="password" id="pass_abrir" class="modal-input" placeholder="Contraseña...">
            <button class="btn-accion" style="background: #3b82f6; color: white;" onclick="ejecutarAccion('abrir_porton')">ABRIR PORTÓN</button>
        </div>
    </div>

    <div id="modalTarjeta" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-cerrar" onclick="cerrarModal('modalTarjeta')">&times;</button>
            <h3 style="color: #8b5cf6; margin-top: 0;">Registrar Llave RFID</h3>
            <input type="text" id="uid_tarjeta" class="modal-input" placeholder="UID (Ej: 3A F4 12 89)">
            <input type="text" id="desc_tarjeta" class="modal-input" placeholder="Descripción (Ej: Llavero Papá)">
            <input type="password" id="pass_tarjeta" class="modal-input" placeholder="Contraseña maestra...">
            <button class="btn-accion" style="background: #8b5cf6; color: white;" onclick="ejecutarAccion('agregar_tarjeta')">GUARDAR TARJETA</button>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>