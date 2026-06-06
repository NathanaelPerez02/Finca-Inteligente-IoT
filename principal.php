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
    <div class="welcome-container" style="max-width: 600px;">
        <h1>🌱 Panel de Control</h1>
        <p>Bienvenido al sistema de monitoreo, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>.</p>
        
        <div class="card" style="background: #121214; margin: 20px 0; max-width: 100%;">
            <p>Aquí se integrarán las lecturas de los sensores IoT y el control de actuadores.</p>
        </div>

        <div class="button-group">
            <a href="logout.php" class="btn" style="background:#f87171; color:white;">Cerrar Sesión</a>
        </div>
    </div>
    <script src="js/main.js"></script>
</body>
</html>