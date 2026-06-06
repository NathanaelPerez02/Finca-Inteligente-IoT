<?php
$hostname = "mysql.railway.internal"; 
$username = "root"; 
$password = "oCeSKpDsYnzHWrJwbfPhTISkvMMuLqiZ"; 
$database = "railway"; 
$port     = "3306"; 

$conn = mysqli_connect($hostname, $username, $password, $database, $port);

if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}
?>