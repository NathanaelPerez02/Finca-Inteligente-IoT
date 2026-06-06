<?php
include("conexion.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = mysqli_real_escape_string($conexion, $_POST['usuario']);
    $password = $_POST['password'];
    
    $password_encriptada = password_hash($password, PASSWORD_BCRYPT);

    $verificar = mysqli_query($conexion, "SELECT * FROM usuarios WHERE usuario = '$usuario'");
    
    if (mysqli_num_rows($verificar) > 0) {
        $mensaje = "<p class='error'>El nombre de usuario ya está en uso.</p>";
    } else {
        $insertar = "INSERT INTO usuarios (usuario, password) VALUES ('$usuario', '$password_encriptada')";
        if (mysqli_query($conexion, $insertar)) {
            $mensaje = "<p class='exito'>¡Registro exitoso! <a href='login.php'>Inicia sesión aquí</a></p>";
        } else {
            $mensaje = "<p class='error'>Error al registrar el usuario.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Finca Inteligente</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <div class="card">
        <h2>🌱 Crear Cuenta</h2>
        
        <?php echo $mensaje; ?>
        
        <form method="POST" action="">
            <input type="text" name="usuario" placeholder="Elige un Usuario" required>
            <input type="password" name="password" placeholder="Elige una Contraseña" required>
            <button type="submit">Registrarme</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="login.php">Volver al Login</a>
        </p>
    </div>

</body>
</html>