<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
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

    <div class="navbar">
        <h2>🚜 Panel Finca Inteligente</h2>
        <div>
            <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <h1>Monitoreo Agrícola e IoT</h1>
        <p class="section-desc">Estado en tiempo real de la arquitectura Maestro-Esclavo.</p>

        <div class="dashboard-grid">
            <div class="card-iot">
                <h3>Temperatura</h3>
                <p class="dato" id="temp-val">-- °C</p>
            </div>
            <div class="card-iot">
                <h3>Humedad del Suelo</h3>
                <p class="dato" id="hum-val">-- %</p>
            </div>
            <div class="card-iot">
                <h3>Acceso Vehicular</h3>
                <p class="dato puerta-cerrada" id="puerta-val">Cerrado</p>
            </div>
        </div>
    </div>

    <script src="js/sensores.js"></script>
</body>
</html>