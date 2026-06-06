<?php
include("conexion.php");

// Se reciben los datos por URL (GET)
if (isset($_GET['usuario']) && isset($_GET['humedad']) && isset($_GET['agua'])) {
    
    $usuario = mysqli_real_escape_string($conn, $_GET['usuario']);
    $humedad_actual = (int)$_GET['humedad'];
    $agua_actual = (int)$_GET['agua'];

    $consulta = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario'");
    
    if (mysqli_num_rows($consulta) > 0) {
        $datos = mysqli_fetch_assoc($consulta);
        
        // Guardar las lecturas en tiempo real en la base de datos
        $sql_actualizar = "UPDATE usuarios SET humedad_actual = $humedad_actual, agua_actual = $agua_actual WHERE usuario = '$usuario'";
        mysqli_query($conn, $sql_actualizar);

        $umbral_humedad = $datos['umbral_humedad'];
        $umbral_agua = $datos['umbral_agua'];
        $email_destino = $datos['email_alertas'];

        $alertas = "";

        // Verificamos si los sensores cayeron por debajo del límite
        if ($humedad_actual < $umbral_humedad) {
            $alertas .= "<h3>⚠️ Alerta de Suelo</h3><p>La humedad está en <b>$humedad_actual%</b>. ¡Necesita riego!</p>";
        }

        if ($agua_actual < $umbral_agua) {
            $alertas .= "<h3>💧 Alerta de Piscina</h3><p>El nivel bajó a <b>$agua_actual%</b>. ¡Revisar reservorio!</p>";
        }

        // Si hay alertas y hay correo destino configurado
        if (!empty($alertas) && !empty($email_destino)) {
            
            // Extraer variables de Railway
            $api_key = getenv('BREVO_API_KEY') ?: $_SERVER['BREVO_API_KEY'];
            $correo_remitente = getenv('CORREO_REMITENTE') ?: $_SERVER['CORREO_REMITENTE'];

            // Preparar el paquete de datos para la API de Brevo
            $datos_api = [
                "sender" => [
                    "name" => "Alertas AgroGate",
                    "email" => $correo_remitente
                ],
                "to" => [
                    ["email" => $email_destino]
                ],
                "subject" => "Alerta Critica - AgroGate",
                "htmlContent" => "<h2>🚨 Sistema de Monitoreo AgroGate</h2>" . $alertas
            ];

            // Configurar la petición HTTP (cURL)
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

            // Ejecutar y capturar respuesta
            $respuesta = curl_exec($ch);
            $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($codigo_http == 201) {
                echo "<b>¡Alerta enviada por correo exitosamente vía API!</b>";
            } else {
                echo "<b>Error de API:</b> " . htmlspecialchars($respuesta);
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