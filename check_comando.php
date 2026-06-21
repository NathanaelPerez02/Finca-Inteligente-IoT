<?php
include("conexion.php");
$usuario = mysqli_real_escape_string($conn, $_GET['usuario']);
$result  = mysqli_query($conn, "SELECT hay_comando FROM usuarios WHERE usuario = '$usuario'");
$fila    = mysqli_fetch_assoc($result);
echo json_encode(["hay_comando" => (int)$fila['hay_comando']]);
?>