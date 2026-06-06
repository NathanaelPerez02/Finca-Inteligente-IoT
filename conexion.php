<?php
$host = "sql311.infinityfree.com"; 
$usuario = "if0_42110124";     
$clave = "HWfJ6KG9o5";      
$base_datos = "if0_42110124_finca";  

$conexion = mysqli_connect($host, $usuario, $clave, $base_datos);

if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}
?>