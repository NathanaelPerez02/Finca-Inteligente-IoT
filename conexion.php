<?php
// Credenciales privadas fijas de Railway
$hostname = "mysql.railway.internal"; 
$username = "root"; 
$password = "oCeSKpDsYnzHWrJwbfPhTISkvMMuLqiZ"; // Tu contraseña real de las capturas
$database = "railway"; // El nombre de la base de datos que vimos que creó Railway
$port     = "3306"; 

// Conexión
$conn = mysqli_connect($hostname, $username, $password, $database, $port);

if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}
?>