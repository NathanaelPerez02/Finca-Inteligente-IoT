<?php
session_start();
include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_dispositivo = 'yeyo'; 
    $valor_distancia = isset($_POST['valor']) ? mysqli_real_escape_string($conn, $_POST['valor']) : null;

    if ($valor_distancia !== null) {
        $sql = "UPDATE usuarios SET acceso_actual = '$valor_distancia' WHERE usuario = '$usuario_dispositivo'";
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

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

$usuario_actual = $_SESSION['usuario'];
$consulta = mysqli_query($conn, "SELECT humedad_actual, agua_actual, acceso_actual FROM usuarios WHERE usuario = '$usuario_actual'");

if ($datos = mysqli_fetch_assoc($consulta)) {
    echo json_encode($datos);
} else {
    echo json_encode(["error" => "Usuario no encontrado"]);
}
?>