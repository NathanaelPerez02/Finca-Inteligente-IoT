<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include("conexion.php");

// Se reciben los datos por URL (GET)
if (isset($_GET['usuario']) && isset($_GET['humedad']) && isset($_GET['agua'])) {
    
    $usuario = mysqli_real_escape_string($conn, $_GET['usuario']);
    $humedad_actual = (int)$_GET['humedad'];
    $agua_actual = (int)$_GET['agua'];

    $consulta = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario'");
    
    if (mysqli_num_rows($consulta) > 0) {
        $datos = mysqli_fetch_assoc($consulta);
        $sql_actualizar_datos = "UPDATE usuarios SET humedad_actual = $humedad_actual, agua_actual = $agua_actual WHERE usuario = '$usuario'";
        mysqli_query($conn, $sql_actualizar_datos);
        $umbral_humedad = $datos['umbral_humedad'];
        $umbral_agua = $datos['umbral_agua'];
        $email_destino = $datos['email_alertas'];

        $alertas = "";

        // Verificamos si los sensores cayeron por debajo del límite
        if ($humedad_actual < $umbral_humedad) {
            $alertas .= "<h3>⚠️ Alerta de Suelo</h3><p>La humedad está en <b>$humedad_actual%</b> (Por debajo del $umbral_humedad% configurado). ¡Necesita riego!</p>";
        }

        if ($agua_actual < $umbral_agua) {
            $alertas .= "<h3>💧 Alerta de Piscina</h3><p>El nivel de agua bajó a <b>$agua_actual%</b> (Umbral: $umbral_agua%). ¡Revisar reservorio!</p>";
        }

        // Si hay alertas y el usuario configuró su correo, se envía el mensaje
        if (!empty($alertas) && !empty($email_destino)) {
            $mail = new PHPMailer(true);

            try {
                // --- MODO DEPURACIÓN Y TIMEOUT ---
                $mail->SMTPDebug = 2; // Activa el "modo chismoso" para ver qué está fallando
                $mail->Timeout   = 10; // Si Google no responde en 10 segundos, aborta y lanza error
                
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                
                $correo_bot = getenv('EMAIL_USER') ?: $_SERVER['EMAIL_USER'];
                $pass_bot   = getenv('EMAIL_PASS') ?: $_SERVER['EMAIL_PASS'];

                $mail->Username   = $correo_bot; 
                $mail->Password   = $pass_bot;
                
                // --- CAMBIAMOS EL PUERTO Y LA SEGURIDAD PARA QUE RAILWAY NO LO BLOQUEE ---
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usamos TLS en lugar de SSL
                $mail->Port       = 587; // Puerto 587 en lugar del 465

                $mail->setFrom($correo_bot, 'Alertas AgroGate');
                $mail->addAddress($email_destino);

                $mail->isHTML(true);
                $mail->Subject = 'Alerta Critica - AgroGate';
                $mail->Body    = "<h2>🚨 Sistema de Monitoreo AgroGate</h2>" . $alertas;
                $mail->AltBody = strip_tags($alertas); 

                $mail->send();
                echo '<br><br><b>¡Alerta enviada por correo exitosamente!</b>';
            } catch (Exception $e) {
                echo "<br><br><b>Error crítico al enviar:</b> {$mail->ErrorInfo} ";
            }
        } else {
            echo "Datos normales, no se requiere enviar alerta. ";
        }
        
    } else {
        echo "Usuario no encontrado.";
    }
} else {
    echo "Faltan parámetros.";
}
?>