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
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                
                // Buscamos la variable en múltiples lugares por compatibilidad con Railway
                $correo_bot = getenv('EMAIL_USER') ?: $_SERVER['EMAIL_USER'];
                $pass_bot   = getenv('EMAIL_PASS') ?: $_SERVER['EMAIL_PASS'];

                $mail->Username   = $correo_bot; 
                $mail->Password   = $pass_bot;
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom($correo_bot, 'Alertas AgroGate');
                $mail->addAddress($email_destino);

                $mail->isHTML(true);
                $mail->Subject = 'Alerta Critica - AgroGate';
                $mail->Body    = "<h2>🚨 Sistema de Monitoreo AgroGate</h2>" . $alertas;
                $mail->AltBody = strip_tags($alertas); 

                $mail->send();
                echo 'Alerta enviada por correo. ';
            } catch (Exception $e) {
                echo "Error al enviar correo: {$mail->ErrorInfo} ";
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