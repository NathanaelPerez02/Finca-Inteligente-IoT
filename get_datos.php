<?php
session_start();
include("conexion.php");

// Verificamos que el usuario tenga sesión iniciada
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

$usuario_actual = $_SESSION['usuario'];

// Buscamos solo los niveles actuales de los sensores
$consulta = mysqli_query($conn, "SELECT humedad_actual, agua_actual FROM usuarios WHERE usuario = '$usuario_actual'");

if ($datos = mysqli_fetch_assoc($consulta)) {
    // Enviamos los datos en formato JSON para que JavaScript los entienda
    echo json_encode($datos);
} else {
    echo json_encode(["error" => "No encontrado"]);
}
?>