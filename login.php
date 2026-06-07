<?php
include("conexion.php");
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificador = mysqli_real_escape_string($conn, $_POST['identificador']);
    $password = $_POST['password'];

    $consulta = "SELECT * FROM usuarios WHERE usuario = '$identificador' OR email_alertas = '$identificador'";
    $resultado = mysqli_query($conn, $consulta);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $datos_usuario = mysqli_fetch_assoc($resultado);
        
        if (password_verify($password, $datos_usuario['password'])) {
            if ($datos_usuario['verificado'] == 1) {
                $_SESSION['usuario'] = $datos_usuario['usuario'];
                header("Location: principal.php");
                exit();
            } else {
                $error = "Tu cuenta aún no está activada. Por favor, haz clic en el enlace que enviamos a tu correo electrónico.";
            }
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "El usuario o correo no existe.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="card">
        <h2>🚜 Iniciar Sesión</h2>
        
        <?php if(!empty($error)) { echo "<p class='error'>$error</p>"; } ?>
        <?php if(isset($_GET['registro']) && $_GET['registro'] == 'pendiente') { echo "<p class='exito' style='color: #fbbf24;'>¡Registro casi listo! Revisa tu correo (y la carpeta de SPAM) para activar tu cuenta.</p>"; } ?>
        
        <form method="POST" action="login.php">
            <input type="text" name="identificador" placeholder="Usuario o Correo Electrónico" required>
            
            <div style="position: relative; width: 100%; margin-bottom: 15px;">
                <input type="password" name="password" id="pass_login" placeholder="Contraseña" required style="width: 100%; box-sizing: border-box; margin-bottom: 0;">
                <button type="button" onclick="togglePass('pass_login', 'eye_login')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #a1a1aa; font-size: 16px;">
                    <i id="eye_login" class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit">Ingresar</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="registro.php">¿No tienes cuenta? Regístrate aquí</a>
        </p>
    </div>

    <script>
        function togglePass(inputId, eyeId) {
            var input = document.getElementById(inputId);
            var eye = document.getElementById(eyeId);
            if (input.type === "password") {
                input.type = "text";
                eye.className = "fas fa-eye-slash"; // Cambia al ícono de ojo tachado
            } else {
                input.type = "password";
                eye.className = "fas fa-eye"; // Vuelve al ícono normal
            }
        }
    </script>
</body>
</html>