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
        /* Mantenemos solo lo que no está en estilos.css (Animaciones y Modales) */
        @keyframes pulso-rojo {
            0% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.5); border-color: rgba(248, 113, 113, 0.6); }
            70% { box-shadow: 0 0 0 10px rgba(248, 113, 113, 0); border-color: rgba(248, 113, 113, 1); }
            100% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0); border-color: rgba(248, 113, 113, 0.6); }
        }
        /* Ajustado al nuevo nombre de la tarjeta */
        .dash-card-ocupada-anim {
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
<body class="dashboard"> <div class="dashboard-container"> <div class="dashboard-header">
            <h1>Panel de Control</h1>
            <p>Bienvenido al sistema de monitoreo, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>.</p>
        </div>
        
        <div class="cards-grid"> <div id="tarjeta_modo" class="dash-card" style="border-color: #10B981;">
                <h3 style="color: white;">MODO DEL SISTEMA</h3>
                <h1 id="estado_modo" class="data-value">CARGANDO...</h1>
                <div class="toggle-buttons">
                    <button onclick="ejecutarAccion('cambiar_modo', 0)" style="background: #4ade80; color: #1e1e24;">AUTO</button>
                    <button onclick="ejecutarAccion('cambiar_modo', 1)" style="background: #fbbf24; color: #1e1e24;">MANUAL</button>
                </div>
            </div>

            <div id="tarjeta_acceso" class="dash-card border-green">
                <h3 id="titulo_acceso" style="color: #ffffff;">ESTADO DE LA PLUMA</h3>
                <h1 id="estado_puerta" class="data-value">CERRADA</h1>
                <p id="distancia_real" style="color: #a1a1aa; font-size: 14px;">Distancia detectada: -- cm</p>
            </div>

            <div class="dash-card">
                <h3 style="color: white;">Nivel de Piscina</h3>
                <h1 id="valor_agua" class="data-value">
                    <?php echo htmlspecialchars($datos_user['agua_actual'] ?? '0'); ?>%
                </h1>
            </div>

            <div class="dash-card">
                <h3 style="color: white;">Humedad Suelo</h3>
                <h1 id="valor_humedad" class="data-value">
                    <?php echo htmlspecialchars($datos_user['humedad_actual'] ?? '0'); ?>%
                </h1>
            </div>
        </div>

        <div class="puerta-controles" style="margin-bottom: 30px;">
            <button id="btn_abrir" class="btn" onclick="abrirModal('modalAbrir')" style="background: #10B981; display: none;">
                Abrir Portón
            </button>
            <button id="btn_cerrar" class="btn" onclick="ejecutarAccion('cerrar_porton')" style="background: #f87171; display: none;">
                Cerrar Portón
            </button>
        </div>

        <div class="dash-card" style="text-align: left; margin-bottom: 30px;">
            <h3 style="color: #4ade80; margin-bottom: 15px;">Configurar Niveles de Alerta</h3>
            <?php if(!empty($mensaje_exito)) { echo "<p class='exito'>$mensaje_exito</p>"; } ?>
            
            <form method="POST" action="principal.php">
                <label style="color: #a1a1aa; font-size: 14px;">Umbral mínimo Humedad Suelo (%):</label>
                <input type="number" name="umbral_humedad" value="<?php echo htmlspecialchars($datos_user['umbral_humedad'] ?? '30'); ?>" required>
                
                <label style="color: #a1a1aa; font-size: 14px; margin-top: 10px; display: block;">Umbral mínimo Nivel Piscina (%):</label>
                <input type="number" name="umbral_agua" value="<?php echo htmlspecialchars($datos_user['umbral_agua'] ?? '30'); ?>" required>
                
                <button type="submit" name="actualizar_umbrales" style="margin-top: 15px;">Guardar Configuración</button>
            </form>
        </div>

        <div class="dash-card" style="margin-bottom: 30px; padding: 0;">
            <h3 style="color: white; margin: 20px 0 15px 0;">Historial de Lecturas</h3>
            <div class="table-responsive">
                <table class="table-historial">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Humedad (%)</th>
                            <th>Piscina (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($query_historial && mysqli_num_rows($query_historial) > 0) {
                            while ($fila = mysqli_fetch_assoc($query_historial)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($fila['fecha']) . "</td>";
                                echo "<td style='color: #fbbf24;'>" . htmlspecialchars($fila['humedad']) . "%</td>";
                                echo "<td style='color: #60a5fa;'>" . htmlspecialchars($fila['agua']) . "%</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>Aún no hay registros en el historial.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>


        <div class="button-group">
            <a href="logout.php" class="btn" style="background:#ff5757; color: #18231d;">Cerrar Sesión</a>
        </div>
    </div>

    <div id="modalAbrir" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-cerrar" onclick="cerrarModal('modalAbrir')">&times;</button>
            <h3 style="color: #18231d; margin-top: 0;">Confirmar Apertura</h3>
            <p style="color: #a1a1aa; font-size: 14px;">Ingrese su contraseña maestra para abrir la tranquera remotamente.</p>
            <input type="password" id="pass_abrir" class="modal-input" placeholder="Contraseña...">
            <button class="btn-accion" style="background: #10B981; color: #18231d;" onclick="ejecutarAccion('abrir_porton')">ABRIR PORTÓN</button>
        </div>
    </div>

    <div id="modalTarjeta" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-cerrar" onclick="cerrarModal('modalTarjeta')">&times;</button>
            <h3 style="color: #10B981; margin-top: 0;">Registrar Llave RFID</h3>
            <input type="text" id="uid_tarjeta" class="modal-input" placeholder="UID (Ej: 3A F4 12 89)">
            <input type="text" id="desc_tarjeta" class="modal-input" placeholder="Descripción (Ej: Llavero Papá)">
            <input type="password" id="pass_tarjeta" class="modal-input" placeholder="Contraseña maestra...">
            <button class="btn-accion" style="background: #10B981; color: #18231d;" onclick="ejecutarAccion('agregar_tarjeta')">GUARDAR TARJETA</button>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>