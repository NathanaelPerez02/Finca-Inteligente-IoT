<?php
include("conexion.php");
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = mysqli_real_escape_string($conn, $_POST['usuario']); 
    $password = $_POST['password'];

    $consulta = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
    $resultado = mysqli_query($conn, $consulta);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $datos_usuario = mysqli_fetch_assoc($resultado);
        
        if (password_verify($password, $datos_usuario['password'])) {
            $_SESSION['usuario'] = $usuario;
            header("Location: principal.php");
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "El usuario no existe.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finca Inteligente</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <div class="card">
        <h2>🚜 Iniciar Sesión</h2>
        
        <?php if(!empty($error)) { echo "<p class='error'>$error</p>"; } ?>
        
        <form method="POST" action="">
            <input type="text" name="usuario" placeholder="Nombre de Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="registro.php">¿No tienes cuenta? Regístrate aquí</a>
        </p>
    </div>

</body>
</html>