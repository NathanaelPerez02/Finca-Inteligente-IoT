<?php
session_start();
include("conexion.php");
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión expirada"]);
    exit();
}

// Recibe los datos del fetch (JavaScript)
$datos = json_decode(file_get_contents('php://input'), true);
$usuario = $_SESSION['usuario'];
$password_ingresada = mysqli_real_escape_string($conn, $datos['password']);
$accion = $datos['accion'];

// VERIFICA LA CONTRASEÑA EN LA BASE DE DATOS
$consulta = mysqli_query($conn, "SELECT contrasena FROM usuarios WHERE usuario = '$usuario'");
$fila = mysqli_fetch_assoc($consulta);


if ($password_ingresada !== $fila['contrasena']) {
    echo json_encode(["exito" => false, "mensaje" => "Contraseña incorrecta"]);
    exit();
}

// EJECUTA LA ACCIÓN SOLICITADA
if ($accion === "abrir_porton") {
    mysqli_query($conn, "UPDATE usuarios SET comando_abrir = 1 WHERE usuario = '$usuario'");
    echo json_encode(["exito" => true, "mensaje" => "¡Orden de apertura enviada al portón!"]);
} 
else if ($accion === "agregar_tarjeta") {
    $uid = mysqli_real_escape_string($conn, strtoupper(trim($datos['uid'])));
    $descripcion = mysqli_real_escape_string($conn, $datos['descripcion']);
    
    // Verifica que no exista ya
    $check = mysqli_query($conn, "SELECT id FROM tarjetas_permitidas WHERE uid = '$uid'");
    if(mysqli_num_rows($check) > 0){
        echo json_encode(["exito" => false, "mensaje" => "Esa tarjeta ya está registrada"]);
        exit();
    }
    
    mysqli_query($conn, "INSERT INTO tarjetas_permitidas (usuario, uid, descripcion) VALUES ('$usuario', '$uid', '$descripcion')");
    echo json_encode(["exito" => true, "mensaje" => "Tarjeta agregada a la lista blanca"]);
}
?>