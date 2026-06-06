<?php
include("conexion.php");
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = mysqli_real_escape_string($conn, $_POST['usuario']);
    $password_plana = $_POST['password'];
    
    $password_encriptada = password_hash($password_plana, PASSWORD_DEFAULT);

    $verificar = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario'");
    if(mysqli_num_rows($verificar) > 0) {
        $error = "El nombre de usuario ya está en uso.";
    } else {
        $sql = "INSERT INTO usuarios (usuario, password) VALUES ('$usuario', '$password_encriptada')";
        if (mysqli_query($conn, $sql)) {
            header("Location: login.php?registro=exitoso");
            exit();
        } else {
            $error = "Error al registrar: " . mysqli_error($conn);
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
        
        <?php if(!empty($error)) { echo "<p class='error'>$error</p>"; } ?>
        
        <form method="POST" action="registro.php">
            <input type="text" name="usuario" placeholder="Elige un Usuario" required>
            <input type="password" name="password" placeholder="Crea una Contraseña" required>
            <button type="submit">Registrarse</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
        </p>
    </div>
    <script src="js/main.js"></script>
</body>
</html>