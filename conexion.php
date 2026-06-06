<?php
if (getenv('MYSQLHOST')) {
    $hostname = "mysql.railway.internal";
    $username = "root";
    $password = "oCeSKpDsYnzHWrJwbfPhTISkvMMuLqiZ";
    $database = "railway";
    $port     = "3306";
} else {
    $hostname = "localhost";
    $username = "root";
    $password = "";
    $database = "railway";
    $port     = "3306";
}

$conn = mysqli_connect($hostname, $username, $password, $database, $port);
$conexion = $conn;

if (!$conn) {
    die("Error crítico de conexión a la Base de Datos: " . mysqli_connect_error());
}
?>