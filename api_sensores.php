<?php
include("conexion.php");

if (isset($_GET['usuario']) && isset($_GET['humedad']) && isset($_GET['agua']) && isset($_GET['acceso']) && isset($_GET['modo'])) {
    $usuario          = mysqli_real_escape_string($conn, $_GET['usuario']);
    $humedad_actual   = (int)$_GET['humedad'];
    $agua_actual      = (int)$_GET['agua'];
    $acceso_actual    = (int)$_GET['acceso'];
    $modo_actual      = (int)$_GET['modo'];
    $estado_tranquera = isset($_GET['estado_tranquera']) ? (int)$_GET['estado_tranquera'] : 0;
    $fecha_timestamp  = isset($_GET['fecha']) ? mysqli_real_escape_string($conn, $_GET['fecha']) : date("Y-m-d_H:i:s");

    $consulta = mysqli_query($conn, "SELECT * FROM usuarios WHERE usuario = '$usuario'");
    if (mysqli_num_rows($consulta) > 0) {
        $datos = mysqli_fetch_assoc($consulta);

        // ── GUARDAR EN HISTORIAL SOLO SI HUMEDAD O AGUA CAMBIARON ──────────────
        $ultima = mysqli_query($conn, "SELECT humedad, agua FROM historial_lecturas 
                                       WHERE usuario = '$usuario' 
                                       ORDER BY id DESC LIMIT 1");
        $hay_cambio = true;

        if ($ultima && mysqli_num_rows($ultima) > 0) {
            $ult = mysqli_fetch_assoc($ultima);
            if ((int)$ult['humedad'] === $humedad_actual && (int)$ult['agua'] === $agua_actual) {
                $hay_cambio = false;
            }
        }

        if ($hay_cambio) {
            mysqli_query($conn, "INSERT INTO historial_lecturas (usuario, humedad, agua, fecha) 
                                 VALUES ('$usuario', $humedad_actual, $agua_actual, '$fecha_timestamp')");
        }
        // ───────────────────────────────────────────────────────────────────────

        // Alertas
        $umbral_humedad = $datos['umbral_humedad'];
        $umbral_agua    = $datos['umbral_agua'];
        $email_destino  = $datos['email_alertas'];
        $ultima_alerta  = $datos['ultima_alerta'];
        $alertas = "";

        if ($humedad_actual < $umbral_humedad) {
            $alertas .= "<h3>Alerta de Suelo</h3><p>La humedad está en <b>$humedad_actual%</b>. ¡Necesita riego!</p>";
        }
        if ($agua_actual < $umbral_agua) {
            $alertas .= "<h3>Alerta de Piscina</h3><p>El nivel bajó a <b>$agua_actual%</b>. ¡Revisar reservorio!</p>";
        }

        if (!empty($alertas) && !empty($email_destino)) {
            $puede_enviar   = true;
            $minutos_espera = 60;

            if (!empty($ultima_alerta)) {
                $minutos_transcurridos = round(abs(time() - strtotime($ultima_alerta)) / 60);
                if ($minutos_transcurridos < $minutos_espera) {
                    $puede_enviar = false;
                }
            }

            if ($puede_enviar) {
                $api_key          = getenv('BREVO_API_KEY') ?: $_SERVER['BREVO_API_KEY'];
                $correo_remitente = getenv('CORREO_REMITENTE') ?: $_SERVER['CORREO_REMITENTE'];

                $datos_api = [
                    "sender"      => ["name" => "Alertas AgroGate", "email" => $correo_remitente],
                    "to"          => [["email" => $email_destino]],
                    "subject"     => "Alerta Critica - AgroGate",
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
                $respuesta   = curl_exec($ch);
                $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($codigo_http == 201) {
                    mysqli_query($conn, "UPDATE usuarios SET ultima_alerta = '" . date('Y-m-d H:i:s') . "' WHERE usuario = '$usuario'");
                }
            }
        }

        // Actualizar valores en tiempo real
        mysqli_query($conn, "UPDATE usuarios SET
                                humedad_actual   = $humedad_actual,
                                agua_actual      = $agua_actual,
                                acceso_actual    = $acceso_actual,
                                modo_actual      = $modo_actual,
                                estado_tranquera = $estado_tranquera
                             WHERE usuario = '$usuario'");

        // Leer comandos pendientes
        $consulta_comandos = mysqli_query($conn, "SELECT comando_abrir, comando_cerrar, comando_modo FROM usuarios WHERE usuario = '$usuario'");
        $comandos  = mysqli_fetch_assoc($consulta_comandos);
        $abrir     = (int)$comandos['comando_abrir'];
        $cerrar    = (int)$comandos['comando_cerrar'];
        $set_modo  = (int)$comandos['comando_modo'];

        // Resetear comandos
        if ($abrir === 1 || $cerrar === 1 || $set_modo !== -1) {
            mysqli_query($conn, "UPDATE usuarios SET comando_abrir = 0, comando_cerrar = 0, comando_modo = -1 WHERE usuario = '$usuario'");
        }

        ob_clean();
        echo json_encode([
            "status"   => "ok",
            "abrir"    => $abrir,
            "cerrar"   => $cerrar,
            "set_modo" => $set_modo
        ]);
        exit();

    } else {
        echo "Usuario no encontrado.";
    }
} else {
    echo "Faltan parámetros.";
}
?>