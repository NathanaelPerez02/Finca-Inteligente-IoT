<?php
session_start();
include("conexion.php");
header('Content-Type: application/json');
 
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión expirada"]);
    exit();
}
 
$datos = json_decode(file_get_contents('php://input'), true);
$usuario = $_SESSION['usuario'];
$accion = $datos['accion'];
 
if ($accion === "abrir_porton") {
    $password_ingresada = $datos['password'];
 
    // CORRECCIÓN BUG 4: la columna es 'password' (no 'contrasena')
    // y la contraseña está hasheada, por eso usamos password_verify()
    $consulta = mysqli_query($conn, "SELECT password FROM usuarios WHERE usuario = '$usuario'");
    $fila = mysqli_fetch_assoc($consulta);
 
    if (!$fila || !password_verify($password_ingresada, $fila['password'])) {
        echo json_encode(["exito" => false, "mensaje" => "Contraseña incorrecta"]);
        exit();
    }
 
    mysqli_query($conn, "UPDATE usuarios SET comando_abrir = 1 WHERE usuario = '$usuario'");
    echo json_encode(["exito" => true, "mensaje" => "¡Orden de apertura enviada!"]);
 
}
else if ($accion === "cambiar_modo") {
    $nuevo_modo = (int)$datos['modo'];
    mysqli_query($conn, "UPDATE usuarios SET comando_modo = $nuevo_modo WHERE usuario = '$usuario'");
    echo json_encode(["exito" => true, "mensaje" => "Cambiando modo..."]);
}
else if ($accion === "agregar_tarjeta") {
    $uid = mysqli_real_escape_string($conn, strtoupper(trim($datos['uid'])));
    $descripcion = mysqli_real_escape_string($conn, $datos['descripcion']);
 
    $check = mysqli_query($conn, "SELECT id FROM tarjetas_permitidas WHERE uid = '$uid'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(["exito" => false, "mensaje" => "Esa tarjeta ya está registrada"]);
        exit();
    }
 
    mysqli_query($conn, "INSERT INTO tarjetas_permitidas (usuario, uid, descripcion) VALUES ('$usuario', '$uid', '$descripcion')");
    echo json_encode(["exito" => true, "mensaje" => "Tarjeta agregada a la lista blanca"]);
}
?>