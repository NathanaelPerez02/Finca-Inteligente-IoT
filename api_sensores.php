<?php
include("conexion.php");

// Se reciben los datos por URL (GET)
if (isset($_GET['usuario']) && isset($_GET['humedad']) && isset($_GET['agua']) && isset($_GET['acceso']) && isset($_GET['modo'])) {
    
    $usuario = mysqli_real_escape_string($conn, $_GET['usuario']);
    $humedad_actual = (int)$_GET['humedad'];
    $agua_actual = (int)$_GET['agua'];
    $acceso_actual = (int)$_GET['acceso'];
    $modo_actual = (int)$_GET['modo'];
    
    // Capturar el estado de la tranquera corrigiendo el guion bajo
    $estado_tranquera = isset($_GET['estado_tranquera']) ? (int)$_GET['estado_tranquera'] : 0;
    
    $fecha_timestamp = isset($_GET['fecha']) ? mysqli_real_escape_string($conn, $_GET['fecha']) : date("Y-m-d_H:i:s");

    $consulta = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario'");
    
    if (mysqli_num_rows($consulta) > 0) {
        $datos = mysqli_fetch_assoc($consulta);
        
        // Guarda en el historial de lecturas
        $sql_historial = "INSERT INTO historial_lecturas (usuario, humedad, agua, fecha) VALUES ('$usuario', $humedad_actual, $agua_actual, '$fecha_timestamp')";
        mysqli_query($conn, $sql_historial);

        $umbral_humedad = $datos['umbral_humedad'];
        $umbral_agua = $datos['umbral_agua'];
        $email_destino = $datos['email_alertas'];
        $ultima_alerta = $datos['ultima_alerta']; 

        $alertas = "";

        // Verificamos si los sensores cayeron por debajo del límite
        if ($humedad_actual < $umbral_humedad) {
            $alertas .= "<h3>Alerta de Suelo</h3><p>La humedad está en <b>$humedad_actual%</b>. ¡Necesita riego!</p>";
        }

        if ($agua_actual < $umbral_agua) {
            $alertas .= "<h3>Alerta de Piscina</h3><p>El nivel bajó a <b>$agua_actual%</b>. ¡Revisar reservorio!</p>";
        }

        // LÓGICA DE ENVÍO DE CORREOS
        if (!empty($alertas) && !empty($email_destino)) {
            
            $puede_enviar = true;
            $minutos_espera = 60; 

            if (!empty($ultima_alerta)) {
                $tiempo_actual = time();
                $tiempo_anterior = strtotime($ultima_alerta);
                $minutos_transcurridos = round(abs($tiempo_actual - $tiempo_anterior) / 60);
                
                if ($minutos_transcurridos < $minutos_espera) {
                    $puede_enviar = false;
                }
            }

            if ($puede_enviar) {
                $api_key = getenv('BREVO_API_KEY') ?: $_SERVER['BREVO_API_KEY'];
                $correo_remitente = getenv('CORREO_REMITENTE') ?: $_SERVER['CORREO_REMITENTE'];

                $datos_api = [
                    "sender" => [
                        "name" => "Alertas AgroGate",
                        "email" => $correo_remitente
                    ],
                    "to" => [
                        ["email" => $email_destino]
                    ],
                    "subject" => "Alerta Critica - AgroGate",
                    "htmlContent" => "<h2>Sistema de Monitoreo AgroGate</h2>" . $alertas
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

                $respuesta = curl_exec($ch);
                $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($codigo_http == 201) {
                    $fecha_hora_actual = date('Y-m-d H:i:s');
                    mysqli_query($conn, "UPDATE usuarios SET ultima_alerta = '$fecha_hora_actual' WHERE usuario = '$usuario'");
                } 
            }
        }

        $sql_actualizar = "UPDATE usuarios SET 
                            humedad_actual = $humedad_actual, 
                            agua_actual = $agua_actual,
                            acceso_actual = $acceso_actual,
                            modo_actual = $modo_actual,
                            estado_tranquera = $estado_tranquera
                            WHERE usuario = '$usuario'";
        mysqli_query($conn, $sql_actualizar);

        // Leer comandos pendientes de la web
        $consulta_comandos = mysqli_query($conn, "SELECT comando_abrir, comando_modo FROM usuarios WHERE usuario = '$usuario'");
        $comandos = mysqli_fetch_assoc($consulta_comandos);
        
        $abrir = (int)$comandos['comando_abrir'];
        $set_modo = (int)$comandos['comando_modo'];
        
        // Resetear comandos en BD para no repetirlos en bucle
        if($abrir === 1 || $set_modo !== -1) {
            mysqli_query($conn, "UPDATE usuarios SET comando_abrir = 0, comando_modo = -1 WHERE usuario = '$usuario'");
        }

        // Respuesta JSON estricta al NodeMCU
        ob_clean(); 
        echo json_encode(["status" => "ok", "abrir" => $abrir, "set_modo" => $set_modo]);
        exit();
        
    } else {
        echo "Usuario no encontrado.";
    }
} else {
    echo "Faltan parámetros.";
}
?>