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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Finca Inteligente</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="dashboard">
    <main class="dashboard-container">

        <header class="dashboard-header">
            <h1>Panel de Control</h1>
            <p>Bienvenido al sistema de monitoreo, <strong>
                    <?php echo htmlspecialchars($_SESSION['usuario']); ?>
                </strong>.</p>
        </header>

        <section class="cards-grid">

            <div id="tarjeta_acceso" class="dash-card border-green">
                <h3 id="titulo_acceso" class="text-green">ACCESO LIBRE</h3>
                <h2 id="estado_acceso">DESPEJADO</h2>
                <p id="distancia_real">Distancia detectada:
                    <?php echo $distancia_inicial; ?> cm
                </p>
            </div>
            <div class="dash-card">
                <h3>MODO DEL SISTEMA</h3>
                <h2 class="text-green">AUTOMÁTICO</h2>
                <div class="toggle-buttons">
                    <button class="btn-active">AUTO</button>
                    <button class="btn-inactive">MANUAL</button>
                </div>
            </div>
            <div class="dash-card">
                <h3 class="text-blue">Nivel de Piscina</h3>
                <h2 id="valor_agua" class="data-value">
                    <?php echo htmlspecialchars($datos_user['agua_actual'] ?? '0'); ?>%
                </h2>
                <p>Umbral de alerta:
                    <?php echo htmlspecialchars($datos_user['umbral_agua'] ?? '30'); ?>%
                </p>
            </div>

            <div class="dash-card">
                <h3 class="text-yellow">Humedad de Suelo</h3>
                <h2 id="valor_humedad" class="data-value">
                    <?php echo htmlspecialchars($datos_user['humedad_actual'] ?? '0'); ?>%
                </h2>
                <p>Umbral de alerta:
                    <?php echo htmlspecialchars($datos_user['umbral_humedad'] ?? '30'); ?>%
                </p>
            </div>

        </section>

        <section class="puerta-controles" style="margin-bottom: 30px;">
            <button class="btn-abrir">Abrir Portón</button>
            <button class="btn-cerrar">Cerrar Portón</button>
        </section>

        <section class="control-section">

            <div class="dash-card card-form">
                <h3 class="text-green">Configurar Niveles de Alerta</h3>
                <?php if(!empty($mensaje_exito)) { echo "<p class='exito'>$mensaje_exito</p>"; } ?>

                <form method="POST" action="principal.php" class="form-vertical">
                    <div class="input-group">
                        <label>Umbral mínimo Humedad Suelo (%):</label>
                        <input type="number" name="umbral_humedad"
                            value="<?php echo htmlspecialchars($datos_user['umbral_humedad'] ?? '30'); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Umbral mínimo Nivel Piscina (%):</label>
                        <input type="number" name="umbral_agua"
                            value="<?php echo htmlspecialchars($datos_user['umbral_agua'] ?? '30'); ?>" required>
                    </div>

                    <button type="submit" name="actualizar_umbrales" class="btn">Guardar Configuración</button>
                </form>
            </div>

            <div class="dash-card card-form">
                <h3 style="text-align: center; color: #ffffff; margin-bottom: 20px;">HISTORIAL DE LECTURAS</h3>

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
                            <tr>
                                <td>2026-06-16_11:24:30</td>
                                <td class="text-yellow">0%</td>
                                <td class="text-blue">0%</td>
                            </tr>
                            <tr>
                                <td>2026-06-16_11:23:58</td>
                                <td class="text-yellow">0%</td>
                                <td class="text-blue">61%</td>
                            </tr>
                            <tr>
                                <td>2026-06-16_11:23:28</td>
                                <td class="text-yellow">0%</td>
                                <td class="text-blue">42%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-card card-form">
                <h3 style="color: #ffffff; margin-bottom: 10px;">Simulador de Hardware</h3>
                <p>Usa este panel para simular los datos que enviaría la placa física.</p>

                <form action="api_sensores.php" target="_blank" method="GET" class="form-horizontal">
                    <input type="hidden" name="usuario"
                        value="<?php echo htmlspecialchars($datos_user['usuario'] ?? 'oldtote'); ?>">
                    <input type="number" name="agua" placeholder="Nivel Piscina (%)" required>
                    <input type="number" name="humedad" placeholder="Humedad Suelo (%)" required>
                    <button type="submit" style="background: #10B981; color: #18231d;">Simular Envío</button>
                </form>
            </div>

        </section>

        <div class="button-group" style="margin-top: 30px;">
            <a href="logout.php" class="btn" style="background:#f87171; color:white;">Cerrar Sesión</a>
        </div>

    </main>

    <script src="js/main.js"></script>
    <script>
        function actualizarSensores() {
            fetch('get_datos.php')
                .then(respuesta => respuesta.json())
                .then(datos => {
                    if (!datos.error) {
                        document.getElementById('valor_agua').innerText = datos.agua_actual + '%';
                        document.getElementById('valor_humedad').innerText = datos.humedad_actual + '%';

                        let distancia = parseInt(datos.acceso_actual) || 100;

                        let tarjeta = document.getElementById('tarjeta_acceso');
                        let tituloAcceso = document.getElementById('titulo_acceso');
                        let textoEstado = document.getElementById('estado_acceso');
                        let textoDistancia = document.getElementById('distancia_real');

                        textoDistancia.innerText = "Distancia detectada: " + distancia + " cm";

                        if (distancia <= 10) {
                            tituloAcceso.innerText = "ZONA OCUPADA";
                            tituloAcceso.style.color = "#f87171";
                            textoEstado.innerText = "ESPERA / RFID";
                            textoEstado.style.color = "#ffffff";
                            tarjeta.style.borderColor = "#f87171";
                            tarjeta.classList.add("card-ocupada-anim");
                        }
                        else if (distancia > 10 && distancia <= 15) {
                            tituloAcceso.innerText = "PRECAUCIÓN";
                            tituloAcceso.style.color = "#fbbf24";
                            textoEstado.innerText = "ACERCÁNDOSE";
                            textoEstado.style.color = "#ffffff";
                            tarjeta.style.borderColor = "#fbbf24";
                            tarjeta.classList.remove("card-ocupada-anim");
                        }
                        else {
                            tituloAcceso.innerText = "ACCESO LIBRE";
                            tituloAcceso.style.color = "#10B981";
                            textoEstado.innerText = "DESPEJADO";
                            textoEstado.style.color = "#ffffff";
                            tarjeta.style.borderColor = "#10B981";
                            tarjeta.classList.remove("card-ocupada-anim");
                        }
                    }
                })
                .catch(error => console.log('Error actualizando sensores:', error));
        }

        setInterval(actualizarSensores, 3000);
    </script>
</body>

</html>