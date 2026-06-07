<?php
session_start();
include("conexion.php");

// 1. SI EL HARDWARE ENVIÓ DATOS (MÉTODO POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_dispositivo = 'yeyo'; // Tu usuario de pruebas de la base de datos
    $valor_agua = isset($_POST['valor']) ? mysqli_real_escape_string($conn, $_POST['valor']) : null;

    if ($valor_agua !== null) {
        $sql = "UPDATE usuarios SET agua_actual = '$valor_agua' WHERE usuario = '$usuario_dispositivo'";
        if (mysqli_query($conn, $sql)) {
            echo "OK_REGISTRADO";
        } else {
            echo "Error_BD: " . mysqli_error($conn);
        }
    } else {
        echo "Datos_Incompletos";
    }
    exit();
}

// 2. SI LA PÁGINA WEB PIDE LOS DATOS (MÉTODO GET)
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

$usuario_actual = $_SESSION['usuario'];
$consulta = mysqli_query($conn, "SELECT humedad_actual, agua_actual FROM usuarios WHERE usuario = '$usuario_actual'");

if ($datos = mysqli_fetch_assoc($consulta)) {
    echo json_encode($datos);
} else {
    echo json_encode(["error" => "Usuario no encontrado"]);
}
?>