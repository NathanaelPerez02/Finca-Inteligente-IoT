<?php
session_start();
include("conexion.php");
header('Content-Type: application/json'); // Le decimos al navegador que esto es un JSON puro

// Verificamos que alguien haya iniciado sesión (sea quien sea)
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

// Tomamos el nombre del usuario que está navegando en la web
$usuario_actual = $_SESSION['usuario'];

// Consultamos los datos, incluyendo el modo
$consulta = mysqli_query($conn, "SELECT humedad_actual, agua_actual, acceso_actual, modo_actual, estado_tranquera FROM usuarios WHERE usuario = '$usuario_actual'");

// Se los enviamos al JavaScript
if ($datos = mysqli_fetch_assoc($consulta)) {
    echo json_encode([
        "error" => false,
        "humedad_actual" => (int)$datos['humedad_actual'],
        "agua_actual" => (int)$datos['agua_actual'],
        "acceso_actual" => (int)$datos['acceso_actual'],
        "modo_actual" => (int)$datos['modo_actual'],
        "estado_tranquera" => (int)$datos['estado_tranquera']
    ]);
} else {
    echo json_encode(["error" => "Usuario no encontrado"]);
}
?>