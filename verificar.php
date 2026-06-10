<?php
include("conexion.php");
$mensaje = "";

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Buscamos si existe un usuario con ese token que aún no esté verificado
    $consulta = mysqli_query($conn, "SELECT id FROM usuarios WHERE token_seguridad = '$token' AND verificado = 0");
    
    if (mysqli_num_rows($consulta) > 0) {
        // Actualizamos la cuenta a verificada
        mysqli_query($conn, "UPDATE usuarios SET verificado = 1 WHERE token_seguridad = '$token'");
        $mensaje = "¡Cuenta activada con éxito! Ya puedes iniciar sesión.";
    } else {
        $mensaje = "El enlace es inválido o la cuenta ya fue activada anteriormente.";
    }
} else {
    $mensaje = "No se proporcionó ningún código de verificación.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación - Finca Inteligente</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="card" style="text-align: center;">
        <h2>Verificación de Cuenta</h2>
        <p style="margin: 20px 0;"><?php echo $mensaje; ?></p>
        <a href="login.php" style="background: #c084fc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ir al Login</a>
    </div>
</body>
</html>