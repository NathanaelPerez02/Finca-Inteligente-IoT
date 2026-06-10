<?php
include("conexion.php");
$error = "";
$exito = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = mysqli_real_escape_string($conn, $_POST['usuario']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_plana = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password_plana !== $confirm_password) {
        $error = "Las contraseñas no coinciden. Inténtalo de nuevo.";
    } else {
        $password_encriptada = password_hash($password_plana, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(16));

        $verificar = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario' OR email_alertas = '$email'");
        if(mysqli_num_rows($verificar) > 0) {
            $error = "El nombre de usuario o el correo ya están registrados.";
        } else {
            $sql = "INSERT INTO usuarios (usuario, password, email_alertas, token_seguridad, verificado) VALUES ('$usuario', '$password_encriptada', '$email', '$token', 0)";
            
            if (mysqli_query($conn, $sql)) {
                $api_key = getenv('BREVO_API_KEY') ?: $_SERVER['BREVO_API_KEY'];
                $correo_remitente = getenv('CORREO_REMITENTE') ?: $_SERVER['CORREO_REMITENTE'];
                
                $enlace = "https://finca-inteligente-iot-production.up.railway.app/verificar.php?token=" . $token;

                $datos_api = [
                    "sender" => ["name" => "AgroGate Seguridad", "email" => $correo_remitente],
                    "to" => [["email" => $email]],
                    "subject" => "Activa tu cuenta en AgroGate",
                    "htmlContent" => "<h2>¡Bienvenido a AgroGate!</h2><p>Para activar tu cuenta y empezar a recibir alertas, por favor haz clic en el siguiente enlace:</p><br><a href='$enlace' style='background:#4ade80; color:black; padding:10px 20px; text-decoration:none; font-weight:bold; border-radius:5px;'>Activar mi Cuenta</a>"
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos_api));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'accept: application/json',
                    'api-key: ' . $api_key,
                    'content-type: application/json'
                ]);

                curl_exec($ch);
                curl_close($ch);

                header("Location: login.php?registro=pendiente");
                exit();
            } else {
                $error = "Error al registrar: " . mysqli_error($conn);
            }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <main class="login-main">
<div class="card">
        <h2>Crear Cuenta</h2>
        
        <?php if(!empty($error)) { echo "<p class='error'>$error</p>"; } ?>
        
        <form method="POST" action="registro.php">
            <input type="text" name="usuario" placeholder="Elige un Usuario" required>
            <input type="email" name="email" placeholder="Correo Electrónico" required>
            
            <div style="position: relative; width: 100%; margin-bottom: 10px;">
                <input type="password" name="password" id="pass1" placeholder="Crea una Contraseña" required style="width: 100%; box-sizing: border-box; margin-bottom: 0;">
                <button type="button" onclick="togglePass('pass1', 'eye1')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #a1a1aa; font-size: 16px;">
                    <i id="eye1" class="fas fa-eye"></i>
                </button>
            </div>

            <div style="position: relative; width: 100%; margin-bottom: 15px;">
                <input type="password" name="confirm_password" id="pass2" placeholder="Confirma tu Contraseña" required style="width: 100%; box-sizing: border-box; margin-bottom: 0;">
                <button type="button" onclick="togglePass('pass2', 'eye2')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #a1a1aa; font-size: 16px;">
                    <i id="eye2" class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit">Registrarse</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
        </p>
    </div>
    </main>
    
    <script src="js/main.js"></script>

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